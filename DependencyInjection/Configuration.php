<?php
/* Copyright (c) 2013 Rhapsody Project
 *
 * Licensed under the MIT License (http://opensource.org/licenses/MIT)
 *
 * Permission is hereby granted, free of charge, to any
 * person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the
 * Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice
 * shall be included in all copies or substantial portions of
 * the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
 * KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT
 * OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Rhapsody\PhingBundle\DependencyInjection;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * <p>
 * Adds the <tt>encrypt</tt> entry into the workflow of the Symfony
 * configuration system. Secure hash encryption can be customized (and in fact
 * is recommended to be overridden), in addition to identifying the encryption
 * routines to use when securing sensitive data in the database.
 * </p>
 *
 * @author Sean.Quinn
 * @since 1.0
 */
class Configuration implements ConfigurationInterface
{

	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder()
	{
		$resourceDir = $this->getResourceDirectory();

		$tb = new TreeBuilder();
		$rootNode = $tb->root('rhapsody_phing');

		$rootNode
			->children()
				->scalarNode('phing_classpath')->cannotBeEmpty()->defaultValue($resourceDir.'/classes')->end()
				->scalarNode('phing_home')->cannotBeEmpty()->defaultValue($resourceDir)->end()
				->scalarNode('root_dir')->cannotBeEmpty()->defaultValue('%kernel.root_dir%/../..')->end()
				->scalarNode('phing')->cannotBeEmpty()->defaultValue('phing.phar')->end()
				->scalarNode('default_file')->cannotBeEmpty()->defaultValue('build.xml')->end()
			->end()
		;
		return $tb;
	}

	private function getResourceDirectory() {
		$dir = dirname(__FILE__);
		$dir .= '..' . DIRECTORY_SEPARATOR . 'Resources';
		return realpath($dir);
	}

}
