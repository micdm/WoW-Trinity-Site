<?php

class TestHelper_Request extends Http_Request
{
	public $get;
	public $post;
	
	public function Get($name = null)
	{
		return isset($this->get[$name]) ? $this->get[$name] : null;
	}
	
	public function Post($name = null)
	{
		return isset($this->post[$name]) ? $this->post[$name] : null;
	}
};
