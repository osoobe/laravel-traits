<?php

namespace Osoobe\LaravelTraits\Test;

use Illuminate\Support\Facades\Route;

trait TestRouteTrait {

    /**
     * Test route
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function checkRoute($path, $status_code=null, $message="") {
        $response = $this->get($path);

        if ( empty($status_code) ) {
            $status_code = [200, 201, 202, 301, 302];
        } else if ( ! is_array($status_code) ) {
            $status_code = [$status_code];
        }

        $this->assertIsArray($status_code);
        $this->assertContains( (int) $response->getStatusCode(), $status_code, "(path: $message $path) ");
        return $response;
    }

    /**
     * Test route
     *
     * @param array|string ...$pages      Each array can have the following info: path, status codes, callback function
     * @example     
     *      $this->checkPages([ '/home' ])
     *      $this->checkPages([ '/home', 200 ])
     *      $this->checkPages([ '/home', [200, 201, 301] ])
     *      $this->checkPages([ '/home', 200, function($response) { $response->assertOk() } ])
     * @return object|array
     */
    public function checkRoutes(...$pages) {
        $responses = [];
        $paths = [];
        foreach($pages as $path) {
            if ( is_array($path) ) {
                
                if ( count($path) > 1 ) {
                    $responses[] = $response = $this->checkRoute($path[0], $path[1]);
                } else {
                    $responses[] = $response = $this->checkRoute($path[0]);
                }

            } else {
                $responses[] = $response = $this->checkRoute($path);
            }

            $paths[] = $path[0];
            if ( !empty($path[2]) ) {
                $callback = $path[2];
                $callback($response);
            }
        }

        return (object) [
            'responses' => $responses,
            'paths' => $paths
        ];
    }



    /**
     * Setup login account for user.
     *
     * @param string $redirect      Expected URL path the user will be redirected to after login
     * @return \Illuminate\Testing\TestResponse $response
     */
    protected function setUpUserLogin($user, string $redirect='/') {
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect($redirect);
        return $response;
    }

    /**
     * Test define routes
     *
     * @param string $middleware            Set the route middleware to test
     * @param array $exclude_pattern        Exlude url paths based on the given pattern
     * @return object|array
     */
    protected function checkDefinedRoutes(string $middleware='web', array $exclude_pattern=[]) {

        $responses = [];
        $paths = [];
        $exclude_patterns = "/(".(implode("|", $exclude_pattern)).")/i";
        
        // Get all laravel routes. Similar to the laravel artisan route:list command
        $routeCollection = Route::getRoutes();

        foreach ($routeCollection as $value) {

            // if route middleware does not match the given middleware skip the current iterated route 
            $middlewares = $value->middleware();
            if ( !empty($middleware) && ! in_array($middleware, $middlewares) ) {
                continue;
            }

            // Get the route info, path and name
            $paths[] = $path = $value->uri();
            $name = $value->getName();
            
            // ignore patterns that need a variable
            $pattern = $pattern = "/\{.*\}/i";

            // test only GET routes
            if ( in_array('GET', $value->methods() ) &&
                 ! preg_match($pattern, $path) &&
                ! empty($name) &&
                ! preg_match($exclude_patterns, $path)
            ) {
                $responses[] = $this->checkRoute($path);
            }
        }

        return (object) [
            'responses' => $responses,
            'paths' => $paths
        ];
    }

}

?>
