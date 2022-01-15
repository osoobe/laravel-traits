<?php

namespace Osoobe\LaravelTraits\Test;

trait TestRouteTrait {

    /**
     * Test route
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function checkRoute($path, $status_code=null, $message="") {
        $response = $this->get($path);

        if ( empty($status_code) ) {
            $status_code = [200, 201, 202, 301];
        } else if ( ! is_array($status_code) ) {
            $status_code = [$status_code];
        }

        $this->assertIsArray($status_code);
        $this->assertContains($status_code, $response->getStatusCode(), "(path: $message $path) ");
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
     * @return void
     */
    public function checkRoutes(...$pages) {
        foreach($pages as $path) {
            if ( is_array($path) ) {
                
                if ( count($path) > 1 ) {
                    $response = $this->checkRoute($path[0], $path[1]);
                } else {
                    $response = $this->checkRoute($path[0]);
                }

            } else {
                $response = $this->checkRoute($path);
            }
            if ( !empty($path[2]) ) {
                $callback = $path[2];
                $callback($response);
            }
        }
    }



    /**
     * Setup login account for user.
     *
     * @param string $redirect      Expected URL path the user will be redirected to after login
     * @return \Illuminate\Testing\TestResponse $response
     */
    protected function setUpUserLogin(string $redirect='/') {
        $response = $this->actingAs($this->user)->get('/login');
        $response->assertRedirect($redirect);
        return $response;
    }

}

?>