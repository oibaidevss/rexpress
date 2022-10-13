<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Api\Callbacks;

use Inc\Base\BaseController;

class AdminCallbacks extends BaseController
{
    public function adminDashboard(){
        return require_once( "$this->plugin_path/templates/admin.php" );
    }

    public function adminSettings(){    
        return require_once( "$this->plugin_path/templates/settings.php" );
    }
}

