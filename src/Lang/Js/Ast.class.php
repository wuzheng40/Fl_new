<?php
/**
 * 
 * JS的语法解析(构建语法树)，借鉴于UglifyJS
 * 支持模版语法
 * @author welefen
 * @version 1.0 - 20120217
 *
 */
class Fl_Js_Ast extends Fl_Base {
	/**
	 * 
	 * token类名
	 * @var string
	 */
	public $tokenClass = 'Fl_Js_Token';
	/**
	 * 
	 * token类实例
	 * @var object
	 */
	public $tokenInstance = null;
	/**
	 * 
	 * 上一个token
	 * @var array or false
	 */
	public $prevToken = false;
	/**
	 * 
	 * 临时存储的token
	 * @var array or false
	 */
	public $peekToken = false;
	/**
	 * 
	 * 当前的token
	 * @var array or false
	 */
	public $currentToken = false;
	/**
	 * 
	 * 构建的语法树是否要带上token的相关信息
	 * @var boolean
	 */
	public $embedToken = false;
	/**
	 * 
	 * 函数深度
	 * @var number
	 */
	public $funtionDepth = 0;
	/**
	 * 
	 * 循环深度
	 * @var number
	 */
	public $loopDepth = 0;
	/**
	 * 
	 * 标签
	 * @var array
	 */
	public $labels = array ();
	/**
	 * 
	 * 执行
	 * @param string $text
	 * @param boolean $embedToken 语法树是否要带上token的信息，如：tokline, tokcol, tokpos, newlineBefore等信息
	 */
	public function run($text = '', $embedToken = false) {
		$this->setText ( $text );
		$this->embedToken = $embedToken;
		$this->tokenInstance = $this->getTokenInstance ();
	}
	/**
	 * 
	 * 
	 */
	public function statementAst() {
		if ($this->isToken ( FL_TOKEN_JS_OPERATOR, "/" ) || $this->isToken ( FL_TOKEN_JS_OPERATOR, "/=" )) {
			$this->peekToken = false;
		}
		switch ($this->currentToken ['type']) {
			case FL_TOKEN_JS_NUMBER :
			case FL_TOKEN_JS_STRING :
			case FL_TOKEN_JS_REGEXP :
			case FL_TOKEN_JS_OPERATOR :
			case FL_TOKEN_JS_ATOM :
				return $this->simpleStatement ();
			case FL_TOKEN_JS_NAME :
				return $this->isToken ( FL_TOKEN_JS_PUNC, ":", $this->peekToken ) ? $this->labeledStatement ( $this->execEach ( $this->currentToken ['value'], 'getNextToken', 'getNextToken' ) ) : $this->simpleStatement ();
			case FL_TOKEN_JS_PUNC :
				switch ($this->currentToken ['value']) {
					case "{" :
						return array ("block", $this->block_ () );
					case "[" :
					case "(" :
						return $this->simpleStatement ();
					case ";" :
						$this->getNextToken ();
						return array ("block" );
					default :
						$this->unexpectTokenError ();
				}
			case FL_TOKEN_JS_KEYWORD :
				$value = $this->currentToken ['value'];
				$this->getNextToken ();
				$keywordMethod = $value . "Statement";
				if (method_exists ( $this, $keywordMethod )) {
					return $this->$keywordMethod ();
				} else {
					$this->unexpectTokenError ();
				}
		}
	}
	public function breakStatement() {
		return $this->breakCont ( "break" );
	}
	public function continueStatement() {
		return $this->breakCont ( "continue" );
	}
	public function debuggerStatement() {
		$this->isSemicolon ();
		return array ("debugger" );
	}
	public function doStatement() {
		$body = $this->statementAst ();
		$this->expectToken ( FL_TOKEN_JS_KEYWORD, "while" );
		$parent = $this->parenthesised ();
		$this->isSemicolon ();
		return array ('do', $parent, $body );
	}
	public function forStatement() {
		$this->expectToken ( FL_TOKEN_JS_PUNC, "(" );
		$init = null;
		if (! $this->isToken ( FL_TOKEN_JS_PUNC, ";" )) {
					
		}
		//处理 for(;xxx) 这种
		$this->expectToken ( FL_TOKEN_JS_PUNC, ";" );
		$test = $this->isToken ( FL_TOKEN_JS_PUNC, ";" ) ? null : $this->expressionAst ();
		$this->expectToken ( FL_TOKEN_JS_PUNC, ";" );
		$step = $this->isToken ( FL_TOKEN_JS_PUNC, ")" ) ? null : $this->expressionAst ();
		$this->expectToken ( FL_TOKEN_JS_PUNC, ")" );
		return array ("for", $init, $test, $step, $this->statementAst () );
	}
	
	public function breakCont() {
	
	}
	public function labeledStatement() {
	
	}
	public function simpleStatement() {
	
	}
	/**
	 * 
	 * 操作符
	 * @param string $left
	 * @param number $minPrec
	 * @param boolean $notIn
	 */
	public function exprOperator($left, $minPrec, $notIn) {
		$op = $this->isToken ( FL_TOKEN_JS_OPERATOR ) ? $this->currentToken ['value'] : null;
		if ($op && $op === 'in' && $notIn) {
			$op = null;
		}
		$prec = ($op != null ? Fl_Js_Static::getPrecedenceValue ( $op ) : null);
		if ($prec != null && $prec > $minPrec) {
			$this->getNextToken ();
			$right = $this->exprOperator ( $this->marybeUnary ( true ), $prec, $notIn );
			return $this->exprOperator ( array ("binary", $op, $left, $right ), $minPrec, $notIn );
		}
		return $left;
	}
	public function exprOperators($notIn) {
		return $this->exprOperator ( $this->maybeUnary ( true ), 0, $notIn );
	}
	/**
	 * 
	 * 可能是一元操作符
	 * @param boolean $allowCalls
	 */
	public function maybeUnary($allowCalls) {
		if ($this->isToken ( FL_TOKEN_JS_OPERATOR ) && Fl_Js_Static::isUnaryPrefix ( $this->currentToken ['value'] )) {
			return $this->makeUnary ( "unary-prefix", $this->execEach ( $this->currentToken ['value'], 'getNextToken' ), $this->maybeUnary ( $allowCalls ) );
		}
		//$val = $this->exprAtomAst ( $allowCalls );
		$val = $this->maybeEmbedTokens ( 'exprAtomAst', $allowCalls );
		while ( $this->isToken ( FL_TOKEN_JS_OPERATOR ) && Fl_Js_Static::isUnarySuffix ( $this->currentToken ['value'] ) && ! $this->currentToken ['newlineBefore'] ) {
			$val = $this->makeUnary ( "unary-postfix", $this->currentToken ['value'], $val );
			$this->getNextToken ();
		}
		return $val;
	}
	public function makeUnary($tag, $op, $expr) {
		if (($op === "++" || $op === "--") && ! $this->isAssignable ( $expr )) {
			$this->throwException ( "Invalid use of " . $op . " operator" );
		}
		return array ($tag, $op, $expr );
	}
	public function exprAtomAst($allowCalls) {
		if ($this->isToken ( FL_TOKEN_JS_OPERATOR, "new" )) {
			$this->getNextToken ();
			$this->new_ ();
		}
		if ($this->isToken ( FL_TOKEN_JS_PUNC )) {
			switch ($this->currentToken ['value']) {
				case "(" :
					$this->getNextToken ();
					return '';
			}
		}
	}
	public function maybeConditional() {
		$expr = $this->exprOps ();
	}
	/**
	 * 
	 * 可能是赋值
	 * @param boolean $notIn
	 */
	public function maybeAssign($notIn) {
		$left = $this->maybeConditional ( $notIn );
		$value = $this->currentToken ['value'];
		if ($this->isToken ( FL_TOKEN_JS_OPERATOR ) && Fl_Js_Static::isAssignment ( $value )) {
			if ($this->isAssignable ( $left )) {
				$this->getNextToken ();
				return array ("assign", Fl_Js_Static::getAssignmentValue ( $value ), $left, $this->maybeAssign ( $notIn ) );
			}
			$this->throwException ( "Invalid assignment" );
		}
		return $left;
	}
	/**
	 * 
	 * 表达式
	 * @param boolean $commas
	 * @param boolean $notIn
	 */
	public function expressionAst($commas = true, $notIn = false) {
		$expr = maybeAssign ( $notIn );
		if ($commas && $this->isToken ( FL_TOKEN_JS_PUNC, "," )) {
			$this->getNextToken ();
			return array ('seq', $expr, $this->expressionAst ( true, $notIn ) );
		}
		return $expr;
	}
	/**
	 * 
	 * 检测是否是某个token，检测类型和值
	 * @param string $type
	 * @param string $value
	 */
	public function isToken($type, $value = false, $token = false) {
		if ($token === false) {
			$token = $this->currentToken;
		}
		return $token ['type'] === $type && ($token ['value'] === $value || $value === false);
	}
	/**
	 * 
	 * 判断当前能否插入分号
	 */
	public function canInsertSemicolon() {
		return $this->currentToken ['newlineBefore'] || $this->isToken ( FL_TOKEN_JS_PUNC, "}" || $this->isToken ( FL_TOKEN_LAST, "" ) );
	}
	/**
	 * 
	 * 判断当前是否是分号token,或者是能否插入分号
	 */
	public function isSemicolon() {
		if ($this->isToken ( FL_TOKEN_JS_PUNC, ";" )) {
			$this->getNextToken ();
		} else if (! $this->canInsertSemicolon ()) {
			$this->unexpectTokenError ();
		}
	}
	/**
	 * 
	 * 获取token
	 */
	public function getNextToken() {
		$this->prevToken = $this->currentToken;
		if ($this->peekToken) {
			$this->currentToken = $this->peekToken;
			$this->peekToken = false;
		} else {
			$this->currentToken = $this->tokenInstance->getNextToken ();
		}
		return $this->currentToken;
	}
	/**
	 * 
	 * 获取下一个token并作为一个临时token存起来
	 */
	public function getPeekToken() {
		if ($this->peekToken) {
			return $this->peekToken;
		}
		return $this->peekToken = $this->tokenInstance->getNextToken ();
	}
	/**
	 * 
	 * 
	 * @param function $fn
	 */
	public function maybeEmbedTokens($fn) {
		$args = func_get_args ();
		array_shift ( $args );
		if ($this->embedToken) {
			$start = $this->currentToken;
		} else {
			return call_user_func_array ( array ($this, $fn ), $args );
		}
	}
	
	/**
	 * 
	 * 执行循环
	 * @param string $fn
	 */
	public function inLoop($fn = '') {
		try {
			++ $this->loopDepth;
			$this->$fn ();
		} catch ( Fl_Exception $e ) {
			//do nothing
		}
		-- $this->loopDepth;
	}
	/**
	 * 
	 * 是否是赋值
	 * @param array $expr
	 */
	public function isAssignable($expr) {
		switch (strval ( $expr [0] )) {
			case "dot" :
			case "sub" :
			case "new" :
			case "call" :
				return true;
			case "name" :
				return $expr [1] != "true";
		}
	}
	function asPropName() {
		switch ($this->currentToken ['type']) {
			case FL_TOKEN_JS_NUMBER :
			case FL_TOKEN_JS_STRING :
				return $this->execEach ( $this->currentToken ['value'], 'getNextToken' );
		}
		return $this->asName ();
	}
	function asName() {
		switch ($this->currentToken ['type']) {
			case FL_TOKEN_JS_NAME :
			case FL_TOKEN_JS_OPERATOR :
			case FL_TOKEN_JS_KEYWORD :
			case FL_TOKEN_JS_ATOM :
				return $this->execEach ( $this->currentToken ['value'], 'getNextToken' );
			default :
				$this->unexpectTokenError ();
		}
	}
	/**
	 * 
	 * 执行每个方法
	 * @param string $fn
	 */
	public function execEach($fn = '') {
		if (method_exists ( $this, $fn )) {
			$fn = $this->$fn ();
		}
		$args = func_get_args ();
		for($i = 1, $count = count ( $args ); $i < $count; $i ++) {
			$this->$args [$i] ();
		}
		return $fn;
	}
	/**
	 * 
	 * 如果类型正确则获取下一个token,不对则抛出异常
	 */
	public function expectToken($type, $value) {
		if ($this->isToken ( $type, $value )) {
			return $this->getNextToken ();
		}
		$this->throwException ( "Unexpected token " . $this->currentToken ['type'] . ", expected " . $type );
	}
	/**
	 * 
	 * 抛出token的相关异常
	 */
	public function throwException($msg = '', $token = false) {
		if ($token === false) {
			$token = $this->currentToken;
		}
		$ext = ' at line:' . ($token ['tokline'] + 1) . ', col:' . ($token ['tokcol'] + 1) . ', pos:' . $token ['tokpos'];
		parent::throwException ( $msg . $ext );
	}
	/**
	 * 
	 * token类型不正确抛出异常
	 * @param array or false $token
	 */
	public function unexpectTokenError($token = false) {
		if ($token === false) {
			$token = $this->currentToken;
		}
		$this->throwException ( "Unexpected token: " . $token ['type'] . " (" + $token ['value'] . ")" );
	}
}