<?php

/*
 * This file is part of the Liquid package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Liquid
 */

// namespace Liquid;

use Liquid\AbstractTag;
use Liquid\Exception\ParseException;
use Liquid\Liquid;
use Liquid\FileSystem;
use Liquid\Regexp;
use Liquid\Context;
use Liquid\Variable;

/**
 * Performs an assignment of one variable to another
 *
 * Example:
 *
 *     {% TagGetPost var = var %}
 *     {% TagGetPost var = "hello" | upcase %}
 */
class TagGetPost extends AbstractTag
{
	/**
	 * @var string The variable to TagGetPost from
	 */
	private $from;

	/**
	 * @var string The variable to TagGetPost to
	 */
	private $to;

	/**
	 * Constructor
	 *
	 * @param string $markup
	 * @param array $tokens
	 * @param FileSystem $fileSystem
	 *
	 * @throws \Liquid\Exception\ParseException
	 */
	public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
	{
		$syntaxRegexp = new Regexp('/(\w+)\s*=\s*(.*)\s*/');

		if ($syntaxRegexp->match($markup)) {
			$this->to = $syntaxRegexp->matches[1];
			$this->from = new Variable($syntaxRegexp->matches[2]);
		} else {
			throw new ParseException("Syntax Error in 'assign' - Valid syntax: assign [var] = [source]");
		}
	}

	/**
	 * Renders the tag
	 *
	 * @param Context $context
	 *
	 * @return string|void
	 */
	public function render(Context $context)
	{
        $output = $this->from->render($context);

        $atts = shortcode_parse_atts($output);

            if( !empty($atts) ){
            $atts['numberposts'] = 1;
            $posts = get_posts($atts);
            if( !empty($posts) ){
                $post = (array) $posts[0];
            }

            $context->set($this->to, $output, true);
        }
	}
}
