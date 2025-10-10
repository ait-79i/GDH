<?php
namespace GDH\Core;

use GDH\Admin\AdminController;
use GDH\Frontend\FrontendController;
use GDH\Shortcodes\ShortcodeManager;
use GDH\Services\Logger;
use GDH\Services\TwigService;


class Plugin
{
    private static $instance = null;
    private $logger;
    private $twig;

    public static function getInstance()
    {
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(){
        $this->logger = new Logger();
        $this->twig = new TwigService();

        // if(is_admin()){
        //     new AdminController($this->logger,$this->twig);
        // }else{
            new FrontendController($this->logger,$this->twig);
        // }

        new ShortCodeManger($this->logger,$this->twig);

         $this->logger->info('Plugin initialisé');
        
    }

        public static function activate()
    {
        // Actions d'activation
    }

    public static function deactivate()
    {
        // Actions de désactivation
    }
}