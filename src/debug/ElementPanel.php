<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\elementspanel\debug;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\events\CancelableEvent;
use craft\events\PopulateElementEvent;
use yii\base\Event;
use yii\debug\Panel;

class ElementPanel extends Panel
{
    /**
     * @var bool
     */
    private $_eagerLoadingOpportunity = false;

    /**
     * @var array
     */
    private $_elements = [];

    /**
     * @var string
     */
    private $_viewPath = '@vendor/putyourlightson/craft-elements-panel/src/views/element/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(ElementQuery::class, ElementQuery::EVENT_BEFORE_PREPARE,
            function(CancelableEvent $event) {
                /** @var ElementQuery $elementQuery */
                $elementQuery = $event->sender;

                $this->_checkEagerLoadingOpportunity($elementQuery);
            }
        );

        Event::on(ElementQuery::class, ElementQuery::EVENT_AFTER_POPULATE_ELEMENT,
            function(PopulateElementEvent $event) {
                $this->_addElement($event->element);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Elements';
    }

    /**
     * @inheritdoc
     */
    public function getSummary()
    {
        return Craft::$app->getView()->render($this->_viewPath.'summary', ['panel' => $this]);
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        return Craft::$app->getView()->render($this->_viewPath.'detail', ['panel' => $this]);
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return [
            'eagerLoadingOpportunity' => $this->_eagerLoadingOpportunity,
            'elements' => $this->_elements,
        ];
    }

    private function _checkEagerLoadingOpportunity(ElementQuery $elementQuery)
    {
        if ($this->_eagerLoadingOpportunity || empty($elementQuery->join)) {
            return;
        }

        $join = $elementQuery->join[0];

        if ($join[0] == 'INNER JOIN' && $join[1] == ['relations' => '{{%relations}}']) {
            $this->_eagerLoadingOpportunity = true;
        }
    }

    private function _addElement(ElementInterface $element)
    {
        $elementType = get_class($element);

        if (empty($this->_elements[$elementType])) {
            $this->_elements[$elementType] = [];
        }

        if (empty($this->_elements[$elementType][$element->getId()])) {
            $this->_elements[$elementType][$element->getId()] = 0;
        }

        $this->_elements[$elementType][$element->getId()]++;
    }
}
