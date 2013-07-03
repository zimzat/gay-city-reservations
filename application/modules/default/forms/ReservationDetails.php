<?php

class Default_Form_ReservationDetails extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

		$this->_addType();
        $this->_addName();
		$this->_addContactDisclaimer();
		$this->_addPhone();
		$this->_addPhoneMessageConsent();
		$this->_addEmail();
		$this->_addContactMethod();

        $this->_addSubmit();
    }

	protected function _addType()
	{
		$this->addElement('radio', 'type', array(
			'label' => 'Select appointment type',
			'required' => true,
			'label_class' => 'form-inline',
			'escape' => false,
		));
	}

    protected function _addName()
    {
        $this->addElement('text', 'name', array(
            'label' => 'Please provide a first name (required)',
            'required' => true,
            'validators' => array(
                new Zend_Validate_StringLength(array('max' => 64)),
				$callback = new Zend_Validate_Callback(array($this, '_notAvailableString'))
            ),
        ));

		$callback->setMessage('You may not use "Available" as part of your name.', Zend_Validate_Callback::INVALID_VALUE);
    }

	public function _addContactDisclaimer()
	{
		$this->addElement(new Base_Form_Element_Placeholder('content-disclaimer', array(
			'value' => 'A phone number is required so that we may confirm your appointment time. Please provide an email address if you would like an email reminder before your appointment at Gay City. This information is kept confidential.',
		)));
	}

	public function _notAvailableString($value)
	{
		return stripos($value, 'Available') === false;
	}

    protected function _addPhone()
    {
        $this->addElement('text', 'phone', array(
            'label' => 'Phone (required)',
            'required' => true,
            'validators' => array(
                new Zend_Validate_StringLength(array('max' => 64)),
				$regex = new Zend_Validate_Regex('#\d{3}\D*\d{3}\D*\d{4}$#'),
            ),
        ));

		$regex->setMessage('Phone number must consist of at least 10 digits, including area code.', Zend_Validate_Regex::NOT_MATCH);
    }

	protected function _addPhoneMessageConsent()
	{
		$this->addElement(new Default_Form_Element_InlineCheckbox('phoneMessageConsent', array(
			'label' => 'Phone Message Consent',
            'required' => false,
			'description' => 'By checking this box you consent to having a detailed message left if we cannot reach you.',
		)));
	}

    protected function _addEmail()
    {
        $this->addElement('text', 'email', array(
            'label' => 'Email (optional)',
            'required' => false,
            'validators' => array(
                new Zend_Validate_StringLength(array('max' => 64)),
				new Zend_Validate_EmailAddress(),
            ),
        ));
    }

    protected function _addContactMethod()
    {
        $this->addElement('select', 'contactMethod', array(
            'label' => 'Preferred Contact Method',
            'required' => false,
            'validators' => array(
                new Zend_Validate_InArray(array('email', 'phone')),
            ),
            'multiOptions' => array('' => 'None', 'email' => 'Email', 'phone' => 'Phone'),
        ));
    }

    protected function _addSubmit()
    {
        $this->addElement('submit', 'submit', array(
            'label' => 'Reserve Appointment',
            'ignore' => true,
			'class' => 'btn btn-success',
        ));
    }
}
