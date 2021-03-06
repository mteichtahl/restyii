<?php

namespace Restyii\Action\Generic;

use \Restyii\Model\ActiveRecord;

class Head extends Base
{
    /**
     * @var string the HTTP verb for this action.
     */
    public $verb = "HEAD";

    /**
     * @inheritDoc
     */
    public function label()
    {
        return \Yii::t('resource', "{resourceLabel} Headers", array(
            '{resourceLabel}' => $this->staticModel()->classLabel()
        ));
    }

    /**
     * @inheritDoc
     */
    public function description()
    {
        $model = $this->staticModel();
        return \Yii::t('resource', "Displays the headers for {collectionLabel}.", array(
            '{collectionLabel}' => $model->classLabel(true),
        ));
    }

    /**
     * Presents the action without performing it.
     * This is used to e.g. show a html form to a user so that they can
     * enter data and *then* perform the action.
     *
     * @param array|boolean $userInput the user input
     * @param mixed|null $loaded the pre-loaded data for the action, if any
     *
     * @return array an array of parameters to send to the `respond()` method
     */
    public function present($userInput, $loaded = null)
    {
        return $this->perform($userInput, $loaded);
    }

    /**
     * Performs the action
     *
     * @param array|boolean $userInput the user input
     * @param mixed|null $loaded the pre-loaded data for the action, if any
     *
     * @return array an array of parameters to send to the `respond()` method
     */
    public function perform($userInput, $loaded = null)
    {
        $model = $this->staticModel();
        $pk = $this->getPkFromContext($model);

        $actions = $this->getActionInstances();
        if ($pk !== null)
            return $this->performItem($actions, $model, $pk);
        else
            return $this->performCollection($actions, $model);
    }

    /**
     * Perform the action on an individual item.
     *
     * @param array $actions the list of existing actions
     * @param ActiveRecord $model the model to perform the action on
     * @param mixed $pk the primary key of the resource
     *
     * @return array an array of parameters to send to the `respond()` method
     */
    protected function performItem($actions, ActiveRecord $model, $pk)
    {
        $model->setScenario('read');
        foreach($actions as $name => $action) {
            if ($action instanceof \Restyii\Action\Collection\Base)
                unset($actions[$name]);
            else
                $actions[$name] = $this->prepareAction($action);
        }
        $attributes = $this->getAttributeConfigs($model);
        return array(200, array(
            'label' => $model->classLabel(),
            'attributes' => $attributes,
            'actions' => $actions
        ));
    }

    /**
     * Perform the action on a collection of items.
     *
     * @param array $actions the list of existing actions
     * @param ActiveRecord $model the model to perform the action on
     *
     * @return array an array of parameters to send to the `respond()` method
     */
    protected function performCollection($actions, ActiveRecord $model)
    {
        $model->setScenario('create');
        foreach($actions as $name => $action) {
            if (!($action instanceof \Restyii\Action\Collection\Base))
                unset($actions[$name]);
            else
                $actions[$name] = $this->prepareAction($action);
        }
        $attributes = $this->getAttributeConfigs($model);
        return array(200, array(
            'label' => $model->classLabel(true),
            'attributes' => $attributes,
            'actions' => $actions
        ));
    }

    /**
     * Gets the configurations for the given model's  attributes
     * @param ActiveRecord $model the active record to get attributes for
     *
     * @return array the attribute config
     */
    protected function getAttributeConfigs(ActiveRecord $model)
    {
        $types = $model->attributeTypes();
        $descriptions = $model->attributeDescriptions();
        $attributes = array();
        foreach($model->getVisibleAttributeNames() as $attribute) {
            $validators = array();
            foreach($model->getValidators($attribute) as $validator /* @var \CValidator $validator */) {
                if ($validator->enableClientValidation && ($js = $validator->clientValidateAttribute($model, $attribute)) !== null)
                    $validators[] = $js;
            }
            $attributes[$attribute] = array(
                'label' => $model->getAttributeLabel($attribute),
                'type' => isset($types[$attribute]) ? $types[$attribute] : null,
                'description' => isset($descriptions[$attribute]) ? $descriptions[$attribute] : null,
                'writable' => $model->isAttributeSafe($attribute),
                'required' => $model->isAttributeRequired($attribute),
                'validators' => $validators,
            );
        }
        return $attributes;
    }



}
