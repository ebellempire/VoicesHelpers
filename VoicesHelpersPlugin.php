<?php
class VoicesHelpersPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'admin_head',
        'admin_footer',
    );
    
    public function hookAdminHead()
    {
        require dirname(__FILE__) . '/functions/functions.php';
    }

    public function hookAdminFooter()
    {
        require dirname(__FILE__) . '/functions/admin_footer.php';
    }
}