<?php

/**
 * Siervo
 *
 * User: max
 * Date: 19/07/2015
 * Time: 20:30
 */

namespace Siervo;

use Exception;

require 'Router.php';

class Siervo{

    /**
     * @var string path absoluto donde se encuentra Siervo.
     */
    public static $_PATH;

    /**
     * @var string nombre del entorno seteado.
     */
    public static $_ENV;

    /**
     * @var string string path relativo donde se ecuentra Siervo.
     */
    public static $_rPATH;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var array() Contiene los distintos
     * entornos registrados (key) y su
     * comportamiento asociado mediante callback (value).
     */
    private $environments;

    public function __construct(){
        $this->setDefaultEnv();
        $this->setEnv();
        $this->setPath();
        $this->setRPath();
        $this->router = new Router();
    }

    /**
     * Run
     *
     * Corre la app.
     *
     */
    public function run(){
        $routes = $this->_getRouteArray();
        $requestUri = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(self::$_rPATH));
        $requestUriArray = explode('/', $requestUri);
        $notFound = false;
        foreach($routes as $route => $callback):
            $route = explode('/', $route);
            $urlsMatch = true;
            $notFound = false;
            $args = array();
            if(count($route) === count($requestUriArray)):
                foreach($requestUriArray as $i => $part):
                    if(substr($route[$i], 0, 1) === ':'):
                        $args[] = $part;
                    elseif($part !== $route[$i]):
                        $urlsMatch = false;
                        break;
                    endif;
                endforeach;
                if($urlsMatch):
                    $callback->bindTo($this, __CLASS__);
                    call_user_func_array($callback, $args);
                    $notFound = true;
                    break;
                endif;
            endif;
        endforeach;
        if(!$notFound):
            echo 'No se encontro la url solicitada..';
        endif;
    }

    /**
     * Set Path
     *
     * Setea el path absoluto donde se encuentra Siervo,
     * y desde ahí setea la raiz de importación.
     *
     * @param string $path
     */
    public function setPath($path = ''){
        self::$_PATH = ($path === '') ? dirname(dirname(__FILE__)) : $path;
        ini_set('include_path', self::$_PATH);
    }

    /**
     * Set R Path
     *
     * Setea el path relativo donde se ejecuta el
     * srcipt de inicio.
     *
     * @param string $rpath
     */
    public function setRPath($rpath = ''){
        self::$_rPATH = ($rpath === '') ? dirname($_SERVER['SCRIPT_NAME']) : $rpath;
    }

    /**
     * Set Env
     *
     * Setea el tipo de entorno de desarrollo.
     *
     * @param string $env
     * @return bool
     */
    public function setEnv($env = 'development'){
        $ok = array_key_exists($env, $this->environments);
        if($ok):
            $callback = $this->environments[$env];
            $ok = is_callable($callback);
            if($ok):
                $callback();
                self::$_ENV = $env;
            endif;
        endif;
        return $ok;
    }

    /**
     * Environment
     *
     * Define un entorno de desarrollo y asocia un
     * compoortamiento mediante una callback al mismo.
     *
     * @param string $env
     * @param null $callback
     */
    public function environment($env = '', $callback = null){
        $this->environments[$env] = $callback;
    }

    /**
     * Set Default Env
     *
     * Setea por defecto dos entornos de desarrollo
     * basicos, development y production, los mismos
     * se pueden reescribir.
     *
     */
    private function setDefaultEnv(){
        $this->environment('development', function(){
            ini_set('error_reporting', E_ALL | E_STRICT | E_NOTICE);
            ini_set('display_errors', 'On');
            ini_set('track_errors', 'On');
        });
        $this->environment('production', function(){
            ini_set('display_errors', 'Off');
        });
    }

    /**
     * Route
     *
     * Le comunica al objeto Router que registre una ruta
     * para encadenar más de un método de petición
     * (ej: route('/dd')->get(..)->post).
     *
     * @param $route
     * @return $this
     */
	public function route($route){		
		return $this->router->route($route);
	}

    /**
     * Get
     *
     * Le comunica al objeto Router que registre
     * una ruta y un comportamiento para esa ruta
     * cuando el request method es GET.
     *
     * @return $this|null|Siervo
     */
	public function get(){
		return call_user_func_array(array($this->router, 'get'), func_get_args());
	}

    /**
     * Post
     *
     * Le comunica al objeto Router que registre
     * una ruta y un comportamiento para esa ruta
     * cuando el request method es POST.
     *
     * @return $this|null|Siervo
     */
	public function post(){
		return call_user_func_array(array($this->router, 'post'), func_get_args());
	}

    /**
     * Put
     *
     * Le comunica al objeto Router que registre
     * una ruta y un comportamiento para esa ruta
     * cuando el request method es PUT.
     *
     * @return $this|null|Siervo
     */
	public function put(){
		return call_user_func_array(array($this->router, 'put'), func_get_args());
	}

    /**
     * Delete
     *
     * Le comunica al objeto Router que registre
     * una ruta y un comportamiento para esa ruta
     * cuando el request method es DELETE.
     *
     * @return $this|null|Siervo
     */
	public function delete(){
		return call_user_func_array(array($this->router, 'delete'), func_get_args());
	}

    /**
     * Get Request Method
     *
     * Retorna el método utilizado con el que el cliente
     * realiza la petición al servidor.
     *
     * @return string
     * @throws Exception
     */
	private function getRequestMethod(){
		$requestMethod = $_SERVER['REQUEST_METHOD'];
		if(($requestMethod === 'POST') && (array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))):
			if($_SERVER === 'DELETE'):
				$requestMethod = 'DELETE';
			elseif($_SERVER['HTTP_X_HTTP_METHOD'] === 'PUT'):
				$requestMethod = 'PUT';
			else:
				throw new Exception('Unexpected Header');
			endif;
		endif;
		return $requestMethod;
	}

    /**
     * _Get Route Array
     *
     * Retorna el array de routes a utilizar
     * dependiendo de método de la petición.
     *
     * @return mixed
     * @throws Exception
     */
    private function _getRouteArray(){
        return $this->router->getRoutes($this->getRequestMethod());
    }
}
