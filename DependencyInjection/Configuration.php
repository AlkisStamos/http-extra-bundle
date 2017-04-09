<?php

namespace Alks\HttpExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Alks\HttpExtraBundle\DependencyInjection
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    protected static $negotiation_context = [
        'type'
    ];

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('alks_http');

        $children = $rootNode->children();
        $headersNode = (new TreeBuilder())->root('headers')->addDefaultsIfNotSet();
        foreach(self::$negotiation_context as $item)
        {
            $children->append($this->negotiationOptionNode($item.'s'));
            $children->append($this->negotiationOptionNode('append_'.$item.'s'));
            $headersNode = $this->negotiationHeadersNode($item,$headersNode);
        }
        $children->append($headersNode);
        $children->arrayNode('negotiation')
            ->children()
            ->booleanNode('enabled')
            ->defaultFalse();
        $children->arrayNode('serializer')
            ->children()
            ->booleanNode('enabled')
            ->defaultTrue();
        $children->arrayNode('normalizer')
            ->children()
            ->booleanNode('enabled')
            ->defaultFalse();
        $children->end();

        $children->arrayNode('validator')
            ->children()
            ->booleanNode('enabled')
            ->defaultFalse();
        $children->end();

        return $treeBuilder;
    }

    /**
     * Sets the template for a negotiation configuration node
     *
     * @param $type
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function negotiationOptionNode($type)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($type);

        $node
            ->prototype('array')
            ->children()
                ->scalarNode('restrict')->defaultNull()->end()
                ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('values')->isRequired()->prototype('scalar')->end()
            ->end()
            ->end();
        return $node;
    }

    /**
     * Sets the template for a headers configuration node
     *
     * @param $type
     * @param ArrayNodeDefinition $node
     * @return ArrayNodeDefinition
     */
    private function negotiationHeadersNode($type, ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('accept_'.$type)->end()
                ->scalarNode('content_'.$type)->end()
            ->end();
        return $node;
    }
}
