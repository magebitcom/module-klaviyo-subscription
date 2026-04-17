<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Component\Layout;

use Magento\Framework\View\Element\UiComponent\BlockWrapperInterface;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Ui\Component\Layout\Tabs as CoreTabs;
use Magento\Customer\Block\Adminhtml\Edit\Tab\View;

/**
 * Overridden class in order to remove newsletter tab from customer page in admin
 */
class Tabs extends CoreTabs
{
    /**
     * Add wrapped layout block
     *
     * @param BlockWrapperInterface $childComponent
     * @param array $areas
     * @return void
     */
    protected function addWrappedBlock(BlockWrapperInterface $childComponent, array &$areas)
    {
        $name = $childComponent->getName();

        if ($name === 'newsletter_content') {
            return;
        }
        /** @var View $block */
        $block = $childComponent->getBlock();
        if (!$block->canShowTab()) {
            return;
        }
        if (!$block instanceof TabInterface) {
            parent::addWrappedBlock($childComponent, $areas);
        }

        $block->setData('target_form', $this->namespace);

        $config = [];
        if ($block->isAjaxLoaded()) {
            $config['url'] = $block->getTabUrl();
        } else {
            $config['content'] = $childComponent->getData('config/content') ?: $block->toHtml();
        }

        $tabComponent = $this->createTabComponent($childComponent, $name);
        $areas[$name] = [
            'type' => $tabComponent->getComponentName(),
            'dataScope' => $name,
            'insertTo' => [
                $this->namespace . '.sections' => [
                    'position' => $block->hasSortOrder() ? $block->getSortOrder() : $this->getNextSortIncrement()
                ]
            ],
            'config' => [
                'label' => $block->getTabTitle()
            ],
            'children' => [
                $name => [
                    'type' => 'html_content',
                    'dataScope' => $name,
                    'config' => $config,
                ]
            ],
        ];
    }
}
