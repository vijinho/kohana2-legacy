<?php defined('SYSPATH') or die('No direct access allowed.');

class Smarty_Demo_Controller extends Controller
{
    // Do not allow to run in production
    const ALLOW_PRODUCTION = false;

    public function index()
    {
        $welcome = new View('demo');
        $welcome->message = "Welcome to the Kohana!";

        $welcome->render(true);
    }
}
