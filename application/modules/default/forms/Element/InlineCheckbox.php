<?php

class Default_Form_Element_InlineCheckbox extends Zend_Form_Element_Checkbox
{
	public function loadDefaultDecorators()
	{
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
			$this->addDecorators(array(
				'ViewHelper',
				'Description' => array('Description', array('tag' => 'span', 'class' => 'description')),
				'DescriptionLabel' => new Zend_Form_Decorator_HtmlTag(array(
					'tag' => 'label',
					'for' => $this->getName(),
					'placement' => 'SURROUND',
					'class' => 'checkbox',
				)),
				'Errors',
				'HtmlTag' => array('HtmlTag', array(
					'tag' => 'dd',
					'id'  => array('callback' => array(get_class($this), 'resolveElementId'))
				)),
				'Label' => array('Label', array('tag' => 'dt')),
			));
		}
	}
}
