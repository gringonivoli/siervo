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

class Siervo{

    public static $_PATH;
    public static $_ENV;
    public static $_rPATH;

	private $getRoutes;
	private $postRoutes;
	private	$putRoutes;
	private	$deleteRoutes;
	private $auxRoute;

    public function __construct(){
        $this->setEnv();
        $this->setPath();
        $this->setRPath();
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
        foreach($routes as $route => $callback):
            $route = explode('/', $route);
            $aux = true;
            $args = array();
            foreach($route as $i => $part):
                if(substr($part, 0, 1) === ':'):
                    $args[] = $requestUriArray[$i];
                elseif($part !== $requestUriArray[$i]):
                    // este caso: http://localhost/siervo-project/hola/juli/tata
                    // para esta definicion: /hola/:name no anda!
                    $aux = false;
                    break;
                endif;
            endforeach;
            $auxT = false;
            if($aux):
                $callback->bindTo($this, __CLASS__);
                call_user_func_array($callback, $args);
                $auxT = true;
                break;
            endif;
        endforeach;
        if(!$auxT):
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
     * @param null $callback
     */
    public function setEnv($env = 'development', $callback = null){
        self::$_ENV = $env;
        if($callback === null):
            switch($env):
                case 'development':
                    ini_set('error_reporting', E_ALL | E_STRICT | E_NOTICE);
                    ini_set('display_errors', 'On');
                    ini_set('track_errors', 'On');
                    break;
                case 'production':
                    ini_set('display_errors', 'Off');
                    break;
            endswitch;
        else:
            if(is_callable($callback)):
                $callback();
            endif;
        endif;
    }

    /**
     * Route
     *
     * Registra una ruta para encadenar más de
     * un método de petición (ej: route('/dd')->get(..)->post).
     *
     * @param $route
     * @return $this
     */
	public function route($route){		
		$this->auxRoute = $route;
		return $this;
	}

    /**
     * Get
     *
     * Registra una ruta y un comportamiento para esa ruta
     * cuando el request method es GET.
     *
     * @return $this|null|Siervo
     */
	public function get(){
		return $this->addRoute(func_get_args(), 'GET');
	}

    /**
     * Post
     *
     * Registra una ruta y un comportamiento para esa ruta
     * cuando el request method es POST.
     *
     * @return $this|null|Siervo
     */
	public function post(){
		return $this->addRoute(func_get_args(), 'POST');
	}

    /**
     * Put
     *
     * Registra una ruta y un comportamiento para esa ruta
     * cuando el request method es PUT.
     *
     * @return $this|null|Siervo
     */
	public function put(){
		return $this->addRoute(func_get_args(), 'PUT');
	}

    /**
     * Delete
     *
     * Registra una ruta y un comportamiento para esa ruta
     * cuando el request method es DELETE.
     *
     * @return $this|null|Siervo
     */
	public function delete(){
		return $this->addRoute(func_get_args(), 'DELETE');
	}

    /**
     * Add Route
     *
     * Agrega una ruta dependiendo de como se llame.
     *
     * @param $args
     * @param $requestType
     * @return $this|null
     */
	private function addRoute($args, $requestType){
		switch(count($args)):
			case 1:
				$this->setArrayRoute($this->auxRoute, $args[0], $requestType);
                return $this;
			case 2:
				$this->setArrayRoute($args[0], $args[1], $requestType);
				return null;
		endswitch;
	}

    /**
     * Set Array Route
     *
     * Registra una ruta y asocia una callback a la
     * misma.
     *
     * @param $key
     * @param $callback
     * @param $requestType
     */
	private function setArrayRoute($key, $callback, $requestType){
		switch($requestType):
			case 'GET':
				$this->getRoutes[$key] = $callback;
				break;
			case 'POST':
				$this->postRoutes[$key] = $callback;
				break;
			case 'PUT':
				$this->putRoutes[$key] = $callback;
				break;
			case 'DELETE':
				$this->deleteRoutes[$key] = $callback;
				break;	
		endswitch;
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
        switch($this->getRequestMethod()):
            case 'GET':
                return $this->getRoutes;
            case 'POST':
                return $this->postRoutes;
            case 'PUT':
                return $this->putRoutes;
            case 'DELETE':
                return $this->deleteRoutes;
        endswitch;
    }

    public function test()
    {
        var_dump(self::$_PATH);
        var_dump(self::$_rPATH);
        var_dump(self::$_ENV);
        var_dump(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        var_dump(array_slice(explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), count(explode('/', self::$_rPATH))));
    }
}
