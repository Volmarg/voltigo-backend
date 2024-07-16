<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    private const PARAMETERS_DIR_MAX_DEPTH_TO_IMPORT = 10;
    private const PARAMETERS_FOLDER_PATH             = "../config/parameters";
    private const PARAMETERS_ENV_FOLDER_PATH         = "../config/parametersEnv";
    private const EXTENSION_YAML                     = "yaml";

    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/config/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');

        $this->importParameters($container);

        $container->import('../config/packages/ambta_doctrine_encrypt.yaml');

        if (is_file(\dirname(__DIR__).'/config/services.yaml')) {
            $container->import('../config/services.yaml');
            $container->import('../config/{services}_'.$this->environment.'.yaml');
        } else {
            $container->import('../config/{services}.php');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/routes.yaml')) {
            $routes->import('../config/routes.yaml');
        } else {
            $routes->import('../config/{routes}.php');
        }
    }

    /**
     * Handles import files with parameters available in {@see ParameterBagInterface}
     * Is required to dynamically load "depth" based parameters files
     *
     * @param ContainerConfigurator $container
     */
    private function importParameters(ContainerConfigurator $container): void
    {
        for ($x = 0; $x <= self::PARAMETERS_DIR_MAX_DEPTH_TO_IMPORT; $x++) {
            $singleDepth = "/*";
            $totalDepth  = "";
            for ($y = 0; $y <= $x; $y++) {
                $totalDepth .= $singleDepth;
            }

            $globalImportPath      = self::PARAMETERS_FOLDER_PATH . $totalDepth . "." . self::EXTENSION_YAML;
            $environmentImportPath = self::PARAMETERS_ENV_FOLDER_PATH . DIRECTORY_SEPARATOR .$this->environment. $totalDepth . "." . self::EXTENSION_YAML;

            $container->import($globalImportPath);
            $container->import($environmentImportPath);
        }
    }

    /**
     * @return string
     */
    public function getPublicDirectoryPath(): string
    {
        return $this->getProjectDir() . "/public";
    }
}
