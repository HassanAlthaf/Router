<?php
namespace Router;

class Router
{
    private $routes = array();
    private $patternForURIs = '/\{[a-zA-Z0-9]*\}/';

    public function createRoute($uri, $class, $method)
    {
        $result = $this->checkWhetherParametersAreRequired($uri);

        if($result != false) {
            $this->routes[] = [$uri, $class, $method, $result];
            return true;
        }

        $this->routes[] = [$uri, $class, $method];
        return true;
    }

    public function checkWhetherParametersAreRequired($uri)
    {
        if(preg_match_all($this->patternForURIs, $uri, $matches)) {
            return $matches[0];
        }
        return false;
    }

    public function runRouter()
    {
        $routesWhichExpectParameters = array();

        foreach($this->routes as $route) {
            if(isset($route[3])) {
                $routesWhichExpectParameters[] = $route;
            }
            if($route[0] === $_SERVER["REQUEST_URI"]) {
                return $route;
            }
        }

        foreach($routesWhichExpectParameters as $route) {
            $route[0] = ltrim($route[0], '/');
            $route[0] = explode('/', $route[0]);
            $partsToConfirm = count($route[0]);

            $requestURI = ltrim($_SERVER["REQUEST_URI"], '/');
            $requestURI = explode('/', $requestURI);
            $partsAvailable = count($requestURI);

            if($partsToConfirm != $partsAvailable) {
                continue;
            }

            $confirmedParts = 0;

            $numberOfParametersToDetect = count($route[3]);
            $numberOfParametersDetected = 0;
            $detectedParameters = array();

            for($iterator = 0; $iterator < $partsToConfirm; $iterator++) {
                if($route[0][$iterator] == $requestURI[$iterator]) {
                    $confirmedParts++;
                    continue;
                } else if(preg_match($this->patternForURIs, $route[0][$iterator])) {
                    $confirmedParts++;
                    $detectedParameters[] = array(
                        $route[3][$numberOfParametersDetected] => $requestURI[$iterator]
                    );

                    $numberOfParametersDetected++;
                    continue;
                }
            }

            if(($numberOfParametersDetected == $numberOfParametersToDetect) && ($partsToConfirm == $confirmedParts)) {
                $route[4] = $detectedParameters;
                return $route;
                break;
            }
        }
        return false;
    }
}