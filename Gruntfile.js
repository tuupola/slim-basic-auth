module.exports = function(grunt) {
    "use strict";

    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),
        watch: {
            js: {
                files: ["*.json", "Gruntfile.js"],
                tasks: ["testjs"]
            },
            php: {
                files: ["src/**/*.php", "test/*.php"],
                tasks: ["testphp"]
            }
        },
        jshint: {
            files: ["*.json", "Gruntfile.js"],
            options: {
                jshintrc: ".jshintrc"
            }
        },
        phpunit: {
            unit: {
                dir: "test"
            },
            options: {
                bin: "vendor/bin/phpunit --bootstrap=vendor/autoload.php --coverage-text --coverage-html ./report",
                //bootstrap: "test/bootstrap.php",
                colors: true,
                testdox: true
            }
        },
        phplint: {
            options: {
                swapPath: "/tmp"
            },
            all: ["src/**/*.php", "test/*.php"]
        },
        phpcs: {
            application: {
                dir: ["src/**/*.php", "test/*.php"]
            },
            options: {
                bin: "vendor/bin/phpcs",
                standard: "PSR2"
            }
        }
    });

    require("load-grunt-tasks")(grunt);

    grunt.registerTask("testjs", ["jshint"]);
    grunt.registerTask("testphp", ["phplint", "phpcs", "phpunit"]);

    grunt.registerTask("default", ["testphp", "testjs"]);

};