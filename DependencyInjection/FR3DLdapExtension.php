<?php

namespace FR3D\LdapBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FR3DLdapExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        foreach (['services', 'security', 'validator', 'ldap_driver'] as $basename) {
            $loader->load(sprintf('%s.xml', $basename));
        }

        $container->setAlias('fr3d_ldap.user_hydrator', $config['service']['user_hydrator']);
        $container->setAlias('fr3d_ldap.ldap_manager', $config['service']['ldap_manager']);
        $container->setAlias('fr3d_ldap.ldap_driver', $config['service']['ldap_driver']);

        // we need this empty array for the intiial construct of the ldap_driver in ldap_driver.xml
        $container->setParameter('fr3d_ldap.ldap_driver.init', []);
    }

    public function getNamespace()
    {
        return 'fr3d_ldap';
    }
}
