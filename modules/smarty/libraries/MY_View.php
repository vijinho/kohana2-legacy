<?php defined('SYSPATH') or die('No direct access allowed.');

class View extends View_Core
{
    public function __construct($name, $data = null, $type = null)
    {
        $smarty_ext = Kohana::config('smarty.templates_ext');

        if (Kohana::config('smarty.integration') == true and Kohana::find_file('views', $name, false, (empty($type) ? $smarty_ext : $type))) {
            $type = empty($type) ? $smarty_ext : $type;
        }

        parent::__construct($name, $data, $type);
    }
}
