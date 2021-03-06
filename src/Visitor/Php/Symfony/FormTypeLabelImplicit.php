<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\Extractor\Visitor\Php\Symfony;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Translation\Extractor\Visitor\Php\BasePHPVisitor;

/**
 * @author Rein Baarsma <rein@solidwebcode.com>
 */
final class FormTypeLabelImplicit extends BasePHPVisitor implements NodeVisitor
{
    use FormTrait;

    public function enterNode(Node $node)
    {
        if (!$this->isFormType($node)) {
            return;
        }

        $domain = null;
        // use add() function and look at first argument and if that's a string
        if ($node instanceof Node\Expr\MethodCall
            && ('add' === $node->name || 'create' === $node->name)
            && $node->args[0]->value instanceof Node\Scalar\String_) {
            // now make sure we don't have 'label' in the array of options
            $customLabel = false;
            if (count($node->args) >= 3) {
                if ($node->args[2]->value instanceof Node\Expr\Array_) {
                    foreach ($node->args[2]->value->items as $item) {
                        if (isset($item->key) && 'label' === $item->key->value) {
                            $customLabel = true;
                        }

                        if (isset($item->key) && 'translation_domain' === $item->key->value) {
                            if ($item->value instanceof Node\Scalar\String_) {
                                $domain = $item->value->value;
                            } elseif ($item->value instanceof Node\Expr\ConstFetch && 'false' === $item->value->name->toString()) {
                                $domain = false;
                            }
                        }
                    }
                }
                // actually there's another case here.. if the 3rd argument is anything else, it could well be
                // that label is set through a static array. This will not be a common use-case so yeah in this case
                // it may be the translation is double.
            }

            // only if no custom label was found, proceed
            if (false === $customLabel && false !== $domain) {
                $label = $node->args[0]->value->value;
                if (!empty($label)) {
                    $this->addLocation($label, $node->getAttribute('startLine'), $node, ['domain' => $domain]);
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
    }

    public function beforeTraverse(array $nodes)
    {
    }

    public function afterTraverse(array $nodes)
    {
    }
}
