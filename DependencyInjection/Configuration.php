<?php

namespace FR3D\LdapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        // we are using the existing configuration for the vayu ldap servers in config.yml with key:app
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app');
        $this->addServiceSection($rootNode);
        return $treeBuilder;
        
    }

    private function addServiceSection(ArrayNodeDefinition $node)
    {
        $node
            ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('service')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('user_hydrator')->defaultValue('fr3d_ldap.user_hydrator.default')->end()
                            ->scalarNode('ldap_manager')->defaultValue('fr3d_ldap.ldap_manager.default')->end()
                            ->scalarNode('ldap_driver')->defaultValue('fr3d_ldap.ldap_driver.zend')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
