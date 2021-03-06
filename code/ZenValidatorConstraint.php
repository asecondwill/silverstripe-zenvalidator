<?php

/**
 * @package ZenValidator
 * @license BSD License http://www.silverstripe.org/bsd-license
 * @author <shea@silverstripe.com.au>
 **/
abstract class ZenValidatorConstraint extends Object{

	/**
	 * @var FormField
	 **/
	protected $field;

	
	/**
	 * @var string
	 **/
	protected $customMessage;


	/**
	 * @var boolean
	 **/
	protected $parsleyApplied;



	/**
	 * Set the field this constraint is applied to
	 * @param FormField $field
	 * @return this
	 **/
	public function setField(FormField $field){
		$this->field = $field;
		return $this;
	}


	public function getField(){
		return $this->field;
	}


	/**
	 * Set a custom message for this constraint
	 * @param String $message
	 * @return this
	 **/
	function setMessage($message){
		$this->customMessage = $message;
		return $this;
	}


	/**
	 * Get's the message that was set on the constrctor or falls back to default
	 * @return string
	 **/
	function getMessage(){
		return $this->customMessage ? $this->customMessage : $this->getDefaultMessage();
	}


	/**
	 * Return the default message for this constraint
	 * @return string
	 **/
	abstract function getDefaultMessage();


	/**
	 * Sets the html attributes required for frontend validation
	 * Subclasses should call parent::applyParsley
	 * @return void
	 **/
	public function applyParsley(){
		if(!$this->field){
			user_error("A constrained Field does not exist on the FieldSet, check you have the right field name for your ZenValidatorConstraint.", E_USER_ERROR);
		}
		$this->parsleyApplied = true;
		if($this->customMessage){
			$this->field->setAttribute(sprintf('data-parsley-%s-message', $this->getConstraintName()), $this->customMessage);	
		}
	}


	/**
	 * Removes the html attributes required for frontend validation
	 * Subclasses should call parent::removeParsley
	 * @return void
	 **/
	public function removeParsley(){
		$this->parsleyApplied = false;
		if($this->field && $this->customMessage){
			$this->field->setAttribute(sprintf('data-parsley-%s-message', $this->getConstraintName()), '');	
		}
	}


	/**
	 * Performs php validation on the value 
	 * @param $value
	 * @return bool
	 **/
	abstract function validate($value);


	/**
	 * Gets the name of this constraint from it's classname which should correspond
	 * to the string that parsley uses to identify a constraint type
	 * @return string
	 **/
	public function getConstraintName(){
		return str_replace('Constraint_', '', $this->class);
	}
	
}


/**
 * Constraint_required 
 * Basic required field form validation
 **/
class Constraint_required extends ZenValidatorConstraint{


	public function applyParsley(){
		parent::applyParsley();
		$this->field->setAttribute('data-parsley-required', 'true');
		$this->field->addExtraClass('required');
	}


	public function removeParsley(){
		parent::removeParsley();
		$this->field->setAttribute('data-parsley-required', 'false');
		$this->field->removeExtraClass('required');
	}


	public function validate($value){
		return $value != '';
	}


	public function getDefaultMessage(){
		return _t('ZenValidator.REQUIRED', 'This field is required');
	}
}


/**
 * Constraint_length
 * Constrain a field value to be a of a min length, max length or between a range  
 *
 * @example Constraint_length::create('min', 5); // minimum length of 5 characters
 * @example Constraint_length::create('max', 5); // maximum length of 5 characters
 * @example Constraint_length::create('range', 5, 10); // length between 5 and 10 characters
 **/
class Constraint_length extends ZenValidatorConstraint{
	
	const MIN = 'min';
	const MAX = 'max';
	const RANGE = 'range';
	
	/**
	 * @var string
	 **/
	protected $type;


	/**
	 * @var int
	 **/
	protected $val1, $val2;


	/**
	 * @param srting $type (min,max,range)
	 * @param int $val1
	 * @param int $val2
	 **/
	function __construct($type, $val1, $val2 = null){
		$this->type = $type;
		$this->val1 = (int)$val1;
		$this->val2 = (int)$val2;
		parent::__construct();
	}


	public function applyParsley(){
		parent::applyParsley();
		switch ($this->type) {
			case 'min':
				$this->field->setAttribute('data-parsley-minlength', $this->val1);
				break;
			case 'max':
				$this->field->setAttribute('data-parsley-maxlength', $this->val1);
				break;
			case 'range':
				$this->field->setAttribute('data-parsley-length', sprintf("[%s,%s]", $this->val1, $this->val2));
				break;
		}
	}
	
	public function getConstraintName(){
		return $this->type;
	}

	public function removeParsley(){
		parent::removeParsley();
		switch ($this->type) {
			case 'min':
				$this->field->setAttribute('data-parsley-minlength', '');
				break;
			case 'max':
				$this->field->setAttribute('data-parsley-maxlength', '');
				break;
			case 'range':
				$this->field->setAttribute('data-parsley-length', '');
				break;
		}
	}


	function validate($value){
		if(!$value) return true;

		switch ($this->type) {
			case 'min':
				return strlen(trim($value)) >= $this->val1;
			case 'max':
				return strlen(trim($value)) <= $this->val1;
			case 'range':
				return strlen(trim($value)) >= $this->val1 && strlen(trim($value)) <= $this->val2;
		}
	}


	function getDefaultMessage(){
		switch ($this->type) {
			case 'min':
				return sprintf(_t('ZenValidator.MINLENGTH', 'This value is too short. It should have %s characters or more'), $this->val1);
			case 'max':
				return sprintf(_t('ZenValidator.MAXLENGTH', 'This value is too long. It should have %s characters or less'), $this->val1);
			case 'range':
				return sprintf(_t('ZenValidator.RANGELENGTH', 'This value length is invalid. It should be between %s and %s characters long'), $this->val1, $this->val2);
		}
	}
}


/**
 * Constraint_value
 * Constrain a field value to be a of a min value, max value or between a range  
 *
 * @example Constraint_value::create('min', 5); // minimum value of 5
 * @example Constraint_value::create('max', 5); // maximum value of 5 
 * @example Constraint_value::create('range', 5, 10); // value between 5 and 10 characters
 **/
class Constraint_value extends ZenValidatorConstraint{

	const MIN = 'min';
	const MAX = 'max';
	const RANGE = 'range';
	
	/**
	 * @var string
	 **/
	protected $type;


	/**
	 * @var int
	 **/
	protected $val1, $val2;


	/**
	 * @param srting $type (min,max,range)
	 * @param int $val1
	 * @param int $val2
	 **/
	function __construct($type, $val1, $val2 = null){
		$this->type = $type;
		$this->val1 = (int)$val1;
		$this->val2 = (int)$val2;
		parent::__construct();
	}


	public function applyParsley(){
		parent::applyParsley();
		switch ($this->type) {
			case 'min':
				$this->field->setAttribute('data-parsley-min', $this->val1);
				break;
			case 'max':
				$this->field->setAttribute('data-parsley-max', $this->val1);
				break;
			case 'range':
				$this->field->setAttribute('data-parsley-range', sprintf("[%s,%s]", $this->val1, $this->val2));
				break;
		}
	}
	
	public function getConstraintName(){
		return $this->type;
	}


	public function removeParsley(){
		parent::removeParsley();
		switch ($this->type) {
			case 'min':
				$this->field->setAttribute('data-parsley-min', '');
				break;
			case 'max':
				$this->field->setAttribute('data-parsley-max', '');
				break;
			case 'range':
				$this->field->setAttribute('data-parsley-range', '');
				break;
		}
	}


	function validate($value){
		if(!$value) return true;

		switch ($this->type) {
			case 'min':
				return (int)$value >= $this->val1;
			case 'max':
				return (int)$value <= $this->val1;
			case 'range':
				return (int)$value >= $this->val1 && (int)$value <= $this->val2;
		}
	}


	function getDefaultMessage(){
		switch ($this->type) {
			case 'min':
				return sprintf(_t('ZenValidator.MIN', 'This value should be greater than or equal to %s'), $this->val1);
			case 'max':
				return sprintf(_t('ZenValidator.MAX', 'This value should be less than or equal to %s'), $this->val1);
			case 'range':
				return sprintf(_t('ZenValidator.RANGE', 'This value should be between %s and %s'), $this->val1, $this->val2);
		}
	}
}

/**
 * Constraint_regex
 * Constrain a field to match a regular expression  
 *
 * @example Constraint_regex::create("/^#(?:[0-9a-fA-F]{3}){1,2}$/"); // value must be a valid hex color
 **/
class Constraint_regex extends ZenValidatorConstraint{

	/**
	 * @var string
	 **/
	protected $regex;


	/**
	 * @param string $regex
	 **/
	function __construct($regex){
		$this->regex = $regex;
		parent::__construct();
	}
	
	public function getConstraintName(){
		return 'pattern';
	}

	public function applyParsley(){
		parent::applyParsley();
		$this->field->setAttribute('data-parsley-pattern', trim($this->regex, '/'));
	}


	public function removeParsley(){
		parent::removeParsley();
		$this->field->setAttribute('data-parsley-pattern', '');
	}


	function validate($value){
		if(!$value) return true;
		return preg_match($this->regex, $value);
	}


	function getDefaultMessage(){
		return _t('ZenValidator.REGEXP', 'This value seems to be invalid');
	}
}


/**
 * Constraint_remote
 * Validate a field remotely via ajax
 *
 * See readme for example
 **/
class Constraint_remote extends ZenValidatorConstraint{

	/**
	 * @var string
	 **/
	protected $url;

	/**
	 * @var array
	 **/
	protected $params;

	/**
	 * @var array
	 **/
	protected $options;

	/**
	 * @var string
	 **/
	protected $validator;
	
	/**
	 * @var string
	 **/
	protected $method = 'GET';


	/**
	 * @param string $url - the url to call via ajax
	 * @param array $params - request vars
	 * @param string $options - array of options like { "type": "POST", "dataType": "jsonp", "data": { "token": "value" } }
	 * @param boolean|string $validator  - custom validator or "reverse"
	 * The following are valid responses from the remote url, with a 200 response code: 1, true, { "success": "..." } and assume false otherwise
     * You can show frontend server-side specific error messages by returning { "error": "your custom message" } or { "message": "your custom message" }
	 **/
	function __construct($url, $params=array(), $options = true, $validator = null){
		$this->url = $url;
		$this->params = $params;
		$this->options = $options;
		$this->validator = $validator;
		
		if(is_array($options) && isset($this->options['type'])) {
			$this->method = $this->options['type'];
		}
		
		parent::__construct();
	}


	public function applyParsley(){
		parent::applyParsley();
		$url = count($this->params) ? $this->url . '?' . http_build_query($this->params) : $this->url;
		$this->field->setAttribute('data-parsley-remote', $url);
		if(!empty($this->options)) $this->field->setAttribute('data-parsley-remote-options', json_encode($this->options));
		if($this->validator) $this->field->setAttribute('data-parsley-remote-validator', $this->validator);
	}


	public function removeParsley(){
		parent::removeParsley();
		$this->field->setAttribute('data-parsley-remote', '');
		if($this->field->getAttribute('data-parsley-remote-options')) $this->field->setAttribute('data-parsley-remote-options', '');
		if($this->field->getAttribute('data-parsley-remote-validator')) $this->field->setAttribute('data-parsley-remote-validator', '');
	}


	function validate($value){
		if(!$value) return true;

		$this->params[$this->field->getName()] = $value;
		$query = http_build_query($this->params);
		$url = $this->method == 'GET' ? $this->url . '?' . $query : $this->url;
			
		// If the url is a relative one, use Director::test() to get the response 
		if(Director::is_relative_url($url)){
			$url = Director::makeRelative($url);
			$postVars = $this->method == 'POST' ? $this->params : null;
			$response = Director::test($url, $postVars = null, Controller::curr()->getSession(), $this->method);
			$result = ($response->getStatusCode() == 200) ? $response->getBody() : 0;		
		// Otherwise CURL to remote url
		}else{
			$ch=curl_init();
			if($this->method == 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'ZENVALIDATOR');
			$result = curl_exec($ch);
			curl_close($ch);
		}
		
		// validate result
		if($result == '1' || $result == 'true'){
			if($this->validator == 'reverse') {
				return false;
			}
			else {
				return true;
			}
		}

		$isJson = ((is_string($result) && (is_object(json_decode($result)) || is_array(json_decode($result))))) ? true : false;

		if($isJson){
			$result = Convert::json2obj($result);
			if(isset($result->success)){
				return true;
			}else{
				if(isset($result->message)){
					$this->setMessage($result->message);
				}elseif(isset($result->error)){
					$this->setMessage($result->error);
				}
			}
		}

		return false;
	}


	function getDefaultMessage(){
		return _t('ZenValidator.REMOTE', 'This value seems to be invalid');
	}
}


/**
 * Constraint_type
 * Constrain a field value to be a of a min value, max value or between a range  
 *
 * @example Constraint_type::create('email'); // require valid email
 * @example Constraint_type::create('url'); // require valid url
 * @example Constraint_type::create('number'); // require valid number
 * @example Constraint_type::create('integer'); // require valid integer
 * @example Constraint_type::create('digits'); // require only digits
 * @example Constraint_type::create('alphanum'); // require valid alphanumeric string
 **/
class Constraint_type extends ZenValidatorConstraint{

	const EMAIL = 'email';
	const URL = 'url';
	const NUMBER = 'number';
	const INTEGER = 'integer';
	const DIGITS = 'digits';
	const ALPHANUM = 'alphanum';


	/**
	 * @var string
	 **/
	protected $type;


	/**
	 * @param string $type - allowed datatype
	 **/
	function __construct($type){
		$this->type = $type;
		parent::__construct();
	}


	public function applyParsley(){
		parent::applyParsley();
		$this->field->setAttribute('data-parsley-type', $this->type);
	}


	public function removeParsley(){
		parent::removeParsley();
		$this->field->setAttribute('data-parsley-type', '');
	}

	
	function validate($value){
		if(!$value) return true;

		switch ($this->type) {
			case 'url':
				return filter_var($value, FILTER_VALIDATE_URL);
			case 'email':
				return filter_var($value, FILTER_VALIDATE_EMAIL);
			case 'number':
				return is_numeric($value);
			case 'integer':
				return is_int($value);
			case 'digits':
				return preg_match('/^[0-9]*$/',$value);
			case 'alphanum':
				return ctype_alnum($value);
		}
	}


	function getDefaultMessage(){
		switch ($this->type) {
			case 'url':
				return _t('ZenValidator.URL', 'This value should be a valid URL');
			case 'email':
				return _t('ZenValidator.EMAIL', 'This value should be a valid email');
			case 'number':
				return _t('ZenValidator.NUMBER', 'This value should be a number');
			case 'integer':
				return _t('ZenValidator.INTEGER', 'This value should be a number');
			case 'digits':
				return _t('ZenValidator.DIGITS', 'This value should be a number');
			case 'alphanum':
				return _t('ZenValidator.URL', 'This value should be alphanumeric');
		}
	}
}


/**
 * Constraint_equalto
 * Constrain a field value to be the same as another field 
 *
 * @example Constraint_equalto::create('OtherField');
 **/
class Constraint_equalto extends ZenValidatorConstraint {

	/**
	 * @var string
	 **/
	protected $targetField;


	/**
	 * @param string $field the Name of the field to match
	 **/
	function __construct($field){
	    $this->targetField = $field;
	    parent::__construct();
	}

	/**
	 * @return FormField
	 */
	public function getTargetField() {
	    return $this->field->getForm()->Fields()->dataFieldByName($this->targetField);
	}

	
	public function applyParsley(){
	    parent::applyParsley();
	    $this->field->setAttribute('data-parsley-equalto', '#' . $this->getTargetField()->getAttribute('id'));
	}


	public function removeParsley(){
	    parent::removeParsley();
	    $this->field->setAttribute('data-parsley-equalto', '');
	}


	function validate($value){
	    return $this->getTargetField()->Value() == $value;
	}


	function getDefaultMessage(){
	    return _t('ZenValidator.EQUALTO', 'This value should be the same as the field "' . $this->getTargetField()->Title() . '"');
	}
}




