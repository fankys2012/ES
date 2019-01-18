<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/18
 * Time: 10:26
 */

namespace frame\base;


interface BootstrapInterface
{
    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app);
}