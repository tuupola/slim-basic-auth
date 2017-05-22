<?php

namespace Slim\Middleware\HttpBasicAuthentication;


use Psr\Http\Message\RequestInterface;

class RequestPathMethodRule
{
    /**
     * Stores all the options passed to the rule
     */
    protected $options = [
        "path" => ["/"],
        "passthrough" => []
    ];

    /**
     * Create a new rule instance
     *
     * @param string[] $options
     *
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @return boolean
     */
    public function __invoke(RequestInterface $request)
    {
        $uri = "/" . $request->getUri()->getPath();
        $uri = preg_replace("#/+#", "/", $uri);

        /** If request path is matches passthrough should not authenticate. */
        foreach ((array)$this->options["passthrough"] as $passthrough => $method) {
            $passthrough = rtrim($passthrough, "/");

            /** If path defined as string, we use this little hack */
            if($passthrough === '0') {
                $passthrough    = $method;
                $method         = null;
            }

            if (preg_match("@^{$passthrough}(/.*)?$@", $uri)) {
                if((in_array(strtolower($request->getMethod()), (array)$method)) || empty((array)$method)) {
                    return false;
                }
            }
        }

        /** Otherwise check if path matches and we should authenticate. */
        foreach ((array)$this->options["path"] as $path => $method) {
            $path = rtrim($path, "/");

            /** If path defined as string, we use this little hack */
            if($path === '0') {
                $path   = $method;
                $method = null;
            }
            if (preg_match("@^{$path}(/.*)?$@", $uri)) {
                if((in_array(strtolower($request->getMethod()), (array)$method)) || empty((array)$method)) {
                    return true;
                }
            }
        }

        return false;
    }
}