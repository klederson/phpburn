<?php
/*
Tasks:
- implement block edit - top level only
- implement zebrastripes
- toc=no
*/
/*
SimpleWiki module, version 1.0 Beta 1, November 24, 2009
copyright (c) Henrik Bechmann, 2009, Toronto, Canada. All rights reserved
licence: http://www.opensource.org/licenses/gpl-3.0.html
Modelled after creole.py and creole2html.py 
	at http://wiki.sheep.art.pl/Wiki%20Creole%20Parser%20in%20Python 
	- author of creole.py and creole2html.py: Radomir Dopieralski
	- many of the regular expressions were based on creole.py
The notions for decorator and block declaration markup were derived in part
	from the wikistyle and directive markup developed for PmWiki (pmwiki.org)
	by its author, Patrick Michaud.
	
Two steps: 
	1. build document tree (parser)
	2. use document tree to generate html (emitter)

Markup extensions:
Arguments can be associated with most document objects 
	through decorators and declarations

Generally, elipsis (...) in the following means arguments:
	- identifier=value ("=" separator) means attribute, 
		- value can be delimited with double or single quotes
	- identifier:value (":" separator) means css style rule, 
		or command:arguments eg. zebrastripes:"white,gray"
		- value can be delimited with double or single quotes
	- value on its own means class or command (eg. zebrastripes)
		referred to as 'style class' or 'method class'
	- argument callouts for classes can be registered 
		with SimpleWiki by client software
	- selectors of callouts can vary interpretation of arguments

decorators must be left-abutted to the objects they decorate

inline decorators => %selector ...% (selector = l,i,s,c)
	- selectors = l (lower case 'L') for list, i for image, s for span, c for code.
	- if l, i or c are not immediately followed by their respective objects, 
		deocrators are returned as text
	- s creates a span
	- %% = (empty inline decorator) is optional close for span decorator
	
block decorators => |:selector ...:| (selector = h,p,ul,ol,li,table,tr,th,td,b,pre)
	- "b" is block divider and creates an empty div

block declaration => (:selector[\d]* ...:)<text>(:selector[\d]*end:)
	- block declarations, both opening and closing tags, 
		must be the first non-space characters of a line
	- opening tags can be followed by text on same line 
		to prevent generation of paragraph markup
	- can be nested based on id number ("[\d]*") 
	- native selectors:
		div, blockquote, 
		table, thead, tbody, tr, td, th, tfoot, caption,
		ul, ol, li, dl, dt, dd

macro => <<macroname ...|text>> as (generally) specified in extended creole
	- can be inline, or act as block on its own line

creole markup is used for basic markup, generally based on
	http://www.wikicreole.org/wiki/Creole1.0 
	http://www.wikicreole.org/wiki/CheatSheet
	http://www.wikicreole.org/wiki/CreoleAdditions
extensions and modifications to creole:
- raw url is not recognized separately from link for performance reasons
- url object does not accept decoration, use link instead
- table markup requires closing (trailing righmost) "|"
- link format => 
	[[url or SymbolicLink:Selector, or #anchor 
		| caption (default = url or SymbolicLink) 
		| title]]
- target anchor written as [[#anchor]]
- image format =>
	{{url or SymbolicLink:Selector
		| caption (default = url or SymbolicLink) 
		| title}}
- symbolic links can be registered by client software
- link and image captions are parsed
- heading text is parsed
- macros can be registered by client software
- no link to wikipage [[WikiPage]], use symbolic links instead
- no alternate link syntax
- no monospace, use inline preformatting instead
- no indented paragraphs, use blockquote or dl block delcarations instead
- no plugin extension per se, though argument callouts, symlinks, and macros 
	can be registered by client software
- superscipts, subscripts, underline, overline, and strikeout are provided with span decorator
- definition lists available with block declarations, not in basic markup

Usage:
	$wiki = new SimpleWiki($raw_text);
	echo $wiki->get_html();

For auto_quicktoc, register the prepared event before getting html:

$wiki = new SimpleWiki($markup);
$wiki->register_events(array('onemit' => array($wiki,'auto_quicktoc')));
$html = $wiki->get_html();

*/

#==========================================================================
#-----------------------------[ SIMPLE WIKI ]------------------------------
#==========================================================================
// Facade and default method classes, macros, events and symlinks
/* 	
	public methods:
	$wiki = new SimpleWiki($markup)  - create object to process markup text
	->parser() - get or set parser
	->prepare($markup) - prepare for get_html(), returns $this
	->emitter() - get or set emitter
	->allow_html($bool = NULL) - get or set allow preformatted blocks to be emitted as html if so decorated
	->register_class_callbacks($callbacks) - ['nodekind']['class']=>$methodref; client responses to classes
	->register_macro_callbacks($callbacks) - ['macroname']=>$methodref; client responses to macros
	->register_events($callbacks) - ['eventname']=>$methodref; client responses to events (onemit, onafteremit)
	->register_symlinks($symlinks) - ['symlink']=>$value
	->register_symlink_handler($handler) - default handler for symlinks not registered
	->get_html() - emit html from text
	->get_metadata() - get metadata from parser
	** note that SimpleWiki registers a number of default behaviours with SimpleWikiEmitter **
*/
class SimpleWiki
{
	protected $_parser;
	protected $_allow_html = TRUE;
	protected $_emitter;
	protected $_footnotes;
	protected $_footnotereferences;
	protected $_working_parser;
	
	public function __construct($text)
	{
		$this->_parser = new SimpleWikiParser($text);
		$this->_emitter = new SimpleWikiEmitter();
		$this->register_class_callbacks(
			array(
				'span' => array(
					'subscript'=> array($this,'callback_span_subscript'),
					'superscript'=> array($this,'callback_span_superscript'),
					'footnote'=> array($this,'callback_span_footnote')),
				'link' => array(
					'newwin' => array($this,'callback_link_newwin')),
				'image' => array(
					'lframe'=> array($this,'callback_image_frame'),
					'rframe'=> array($this,'callback_image_frame')),
				'paragraph' => array(
					'nop'=> array($this,'callback_paragraph_nop'),
					'div'=> array($this,'callback_paragraph_div')),
				'blockdef' => array(
					'lframe'=> array($this,'callback_blockdef_frame'),
					'rframe'=> array($this,'callback_blockdef_frame')),
				'preformatted' => array(
					'html'=> array($this,'callback_pre_html')),
				'code' => array(
					'html'=> array($this,'callback_code_html'))
			)
		);
		$this->register_macro_callbacks(
			array(
				'quicktoc' => array($this,'macro_quicktoc')
			)
		);
		$this->register_symlinks(array('Anchor'=>'','Local'=>''));
	}
	public function parser($parser = NULL)
	{
		if (!is_null($parser))
			$this->_parser = $parser;
		return $this->_parser;
	}
	public function prepare($raw)
	{
		$this->_parser->prepare($raw);
		return $this;
	}
	public function emitter($emitter = NULL)
	{
		if (!is_null($emitter))
			$this->_emitter = $parser;
		return $this->_emitter;
	}
	public function allow_html($bool = NULL)
	{
		if (!is_null($bool))
			$this->_allow_html = $bool;
		return $this->_allow_html;
	}
	public function get_html()
	{
		$dom = $this->_parser->parse();
		return $this->_emitter->emit($dom);
	}
	public function get_metadata()
	{
		return $this->_parser->metadata();
	}
	public function register_class_callbacks($callbacks) // ['nodekind']['class']=>$methodref
	{
		$emitter = $this->_emitter;
		$emitter->register_class_callouts($callbacks);
	}
	public function register_macro_callbacks($callbacks) // ['macroname']=>$methodref
	{
		$emitter = $this->_emitter;
		$emitter->register_macro_callouts($callbacks);
	}
	public function register_events($callbacks) // ['eventname']=>$methodref
	{
		$emitter = $this->_emitter;
		$emitter->register_events($callbacks);
	}
	public function register_symlinks($symlinks) // ['symlink']=>$value
	{
		$emitter = $this->_emitter;
		$emitter->register_symlinks($symlinks);
	}
	public function register_symlink_handler($handler)
	{
		$emitter = $this->_emitter;
		$emitter->register_symlink_handler($handler);
	}
	#-----------------------------[ DEFAULT CLASS CALLBACKS ]-----------------------------#
	public function callback_paragraph_nop($node)
	{
		$node->prefix = '';
		$node->prefixtail = '';
		$node->postfix = '';
		$node->decorator = new StdClass();
		return $node;
	}
	public function callback_paragraph_div($node)
	{
		$node->prefix = '<div';
		$node->prefixtail = '>';
		$node->postfix = '</div>';
		unset($node->decorator->classes[array_search('div',$node->decorator->classes)]);
		return $node;
	}
	public function callback_code_html($node)
	{
		if ($this->_allow_html)
		{
			$node->prefix = '';
			$node->prefixtail = '';
			$node->postfix = '';
			$node->escapecontent = FALSE;
			$node->decorator = new StdClass();
		}
		return $node;
	}
	public function callback_blockdef_frame($node)
	{
		if ($node->blocktag != 'div') return $node;
		$node->decorator->classes[] = 'frame';
		return $node;
	}
	public function callback_image_frame($node)
	{
		$lframeindex = array_search('lframe',$node->decorator->classes);
		$rframeindex = array_search('rframe',$node->decorator->classes);
		if ($lframeindex === FALSE) // must be rframe
		{
			unset($node->decorator->classes[$rframeindex]);
			$orientation = 'rframe';
		}
		else // must be lframe;
		{
			unset($node->decorator->classes[$lframeindex]);
			$orientation = 'lframe';
		}
		$prefix = $node->prefix;
		$prefixtail = $node->prefixtail;
		$prefix = "<div class='frame $orientation'>" . $prefix;
		$prefixtail .= "<br>{$node->caption}</div>";
		$node->prefix = $prefix;
		$node->prefixtail = $prefixtail;
		return $node;
	}
	public function callback_pre_html($node)
	{
		if ($this->_allow_html)
		{
			$node->prefix = '';
			$node->prefixtail = '';
			$node->postfix = '';
			$node->escapecontent = FALSE;
			$node->decorator = new StdClass();
		}
		return $node;
	}
	public function callback_span_footnote($node)
	{
		$footnotes = $this->_footnotes;
		if (empty($footnotes)) // inititalize footnote system
		{
			$footnotes = new StdClass;
			$footnotes->count = 0;
			$footnotes->list = array();
			if (empty($this->_working_parser))
				$this->_working_parser = new SimpleWikiParser('');
			$this->register_events(
				array('onafteremit' =>
					array($this,'render_footnotes')));
		}
		// set aside footnote
		$footnote = $footnotes->list[] = $node;
		$count = $footnote->id = ++$footnotes->count;
		// generate markup for link
		$parser = $this->_working_parser;
		$markup = 
			'%s superscript%[[#footnotemarker'
			. $count
			. ']][[#footnote'
			. $count
			. '|['
			. $count.']]]%%';
		$dom = $parser->prepare($markup)->parse();
		// replace footnote body with reference in body of text
		$span = $dom->children[0]->children[0]; // document/paragraph
		$span->parent = $footnote->parent;
		$footnote->parent = NULL;
		$this->_footnotes = $footnotes;
		// create lookups for referenced footnotes
		$footnote->rendered = FALSE;
		$footnotereference = @$footnote->decorator->attributes['footnotereference'];
		if (!empty($footnotereference))
		{
			$this->_footnotereferences[$footnotereference][] = $footnote;
		}
		// fix and return footnote link span
		$span->infix = $this->_emitter->emit_children($span);
		$span = $this->callback_span_superscript($span); // set html elements
		return $span;
	}
	# triggered at onafteremit event...
	public function render_footnotes($document)
	{
		$footnotes = $this->_footnotes->list;
		$markup = '';
		foreach ($footnotes as $footnote)
		{
			if ($footnote->rendered) continue; // has been rendered as reference to other
			// render base footnote
			$id = $footnote->id;
			$markup .=
				'* [[#footnotemarker' . $id . '|^]][' . $id .'][[#footnote' . $id . ']]';
			$footnotename = @$footnote->decorator->attributes['footnotename'];
			if (!empty($footnotename)) // possibly referenced by others
			{
				$references = @$this->_footnotereferences[$footnotename];
				if (!empty($references)) // add references
				{
					foreach ($references as $reference)
					{
						$ref = $reference->id;
						$markup .= 
							' [[#footnotemarker' 
							. $ref 
							. '|^]][' 
							. $ref 
							.'][[#footnote' 
							. $ref 
							. ']]';
						$reference->rendered=true;
					}
				}
			}
			$infix = $footnote->infix;
			$infix = preg_replace('/\\n/','',$infix); // to allow \\ (newline) markup in footnotes
			$markup .=
				' %c html%{{{'
				. $infix
				. "}}}\n";
		}
		// wrap footnote block
		$markup = 
			"----\n"
			. "(:div footnoteblock:)\n======Footnotes:======\n"
			. $markup
			. "(:divend:)\n";
		// allow html
		$allowhtml = $this->allow_html();
		$this->allow_html(TRUE);
		$wiki = new SimpleWiki($markup);
		$wiki->register_symlinks($this->emitter()->symlinks());
		$wiki->register_symlink_handler($this->emitter()->symlink_handler());
		$document->postfix .= $wiki->get_html();
		$this->allow_html($allowhtml);
		return $document;
	}
	public function callback_span_superscript($node)
	{
		$node->prefix = '<sup';
		$node->postfix = '</sup>';
		unset($node->decorator->classes[array_search('superscript',$node->decorator->classes)]);
		return $node;
	}
	public function callback_span_subscript($node)
	{
		$node->prefix = '<sub';
		$node->postfix = '</sub>';
		unset($node->decorator->classes[array_search('subscript',$node->decorator->classes)]);
		return $node;
	}
	public function callback_link_newwin($node)
	{
		$node->decorator->attributes['target'] = '_blank';
		unset($node->decorator->classes[array_search('newwin',$node->decorator->classes)]);
		return $node;
	}
	#-----------------------------[ DEFAULT MACROS ]----------------------------------#
	// enclosing div is given class 'quicktoc-platform'
	public function macro_quicktoc($node)
	{
		$caption = $node->caption;
		if (!$caption) $caption = 'Table of contents';
		# move to root of document
		$document = $this->_parser->up_to($node,array('document'));
		# collect all headings
		$contents = array();
		$contents = $this->macro_quicktoc_assemble_headings($document,$contents);
		# set data for content line items
		$contentheadings = array();
		$count = 0;
		foreach ($contents as $heading)
		{
			// assign id
			$count++;
			$sessionid = 'heading' . $count;
			$headingid = @$heading->decorator->attributes['id'];
			if (is_null($headingid))
			{
				$headingid = $heading->decorator->attributes['id'] = $sessionid;
			}
			$heading->decorator->attributes['contentsid'] = $sessionid;
			$contentheading = new StdClass();
			$contentheading->id = $headingid;
			// assign text
			$contentheading->text = $this->_emitter->emit_node_text($heading);
			// assign level
			$contentheading->level = $heading->level;
			$contentheading->nesting = $heading->nesting;
			$contentheadings[] = $contentheading;
		}
		# generate markup for table of contents
		if (!empty($contentheadings))
		{
			$markup = '';
			// calculate relative depth, beginning with 1
			$contentdepth = 1;
			$previouscontentdepth = $contentdepth;
			$contentdepthstack = array();
			$flooroffset = 0; // lowest depth, controlled by nesting
			$flooroffsetstack = array();
			// make sure there is no change for first item 
			//	- markup requires starting depth of 1
			$previouslevel = $contentheadings[0]->level;
			$previouslevelstack = array();
			$previousnestinglevel = $contentheadings[0]->nesting; 
			// process collected elements
			foreach ($contentheadings as $contentheading)
			{
				// calculate depth
				$level = $contentheading->level;
				$nestinglevel = $contentheading->nesting;
				if ($nestinglevel > $previousnestinglevel)
				{ // save state
					array_push($flooroffsetstack,$flooroffset);
					array_push($contentdepthstack,$contentdepth);
					array_push($previouslevelstack,$previouslevel);
					// set floor
					$flooroffset = $contentdepth;
				}
				elseif ($nestinglevel < $previousnestinglevel)
				{ // restore state
					if (!empty($flooroffsetstack))
					{
						$flooroffset = array_pop($flooroffsetstack);
						$contentdepth = array_pop($contentdepthstack);
						$previouslevel = array_pop($previouslevelstack);
					}
				}
				if ($level > $previouslevel) 
					$contentdepth++;
				elseif ($level < $previouslevel) 
					$contentdepth--;
				$contentdepth = min($level,$contentdepth);
				$contentdepth = max($contentdepth,1);
				$previouslevel = $level;
				$previousnestinglevel = $nestinglevel;
				$previouscontentdepth = $contentdepth;
				// generate markup
				$markup .= 
					str_repeat('*',$contentdepth + $flooroffset) 
					. '[[#'
					. $contentheading->id 
					. '|' 
					. $contentheading->text
					. "]]\n";
			}
			// enclose markup
			$caption = preg_replace('/\\n/','',$caption); // to allow \\ (newline) markup in caption
			$markup = 
				"(:div id=quicktoc-platform:)\n
					(:div1 id=quicktoc-header:)\n
						|:p div id=quicktoc-caption quicktoc-closed:|%c html%{{{" . $caption . "}}}\n
					(:div1end:)\n
					(:div1 id=quicktoc-body:)\n" 
						. $markup . "\n
					(:div1end:)\n
				(:divend:)\n";
			# generate html for table of contents
			// allow html
			$allowhtml = $this->allow_html();
			$this->allow_html(TRUE);
			$wiki = new SimpleWiki($markup);
			$wiki->register_symlinks($this->emitter()->symlinks());
			$wiki->register_symlink_handler($this->emitter()->symlink_handler());
			$node->output = $wiki->get_html();
			$this->allow_html($allowhtml);
		}
		return $node;
	}
	protected function macro_quicktoc_assemble_headings($node,$contents)
	{
		static $nesting = -1;
		$nesting++;
		if ($node->kind == 'heading')
		{
			$node->nesting = $nesting;
			$contents[] = $node;
		}
		else
		{
			$children = $node->children;
			if (!empty($children))
			{
				foreach ($children as $child)
				{
					$contents = $this->macro_quicktoc_assemble_headings($child,$contents);
				}
			}
		}
		$nesting--;
		return $contents;
	}
	#----------------------[ NATIVE EVENT CALLBACKS ]--------------------------#
	// $wiki->register_events(array('onemit' =>array($wiki,'auto_quicktoc')));
	public function auto_quicktoc($document)
	{
		$markup = '<<quicktoc>>';
		if (empty($this->_working_parser))
			$this->_working_parser = new SimpleWikiParser('');
		$parser = $this->_working_parser;
		$dom = $parser->prepare($markup)->parse();
		$tocnode = $dom->children[0];
		$tocnode->parent = $document;
		array_unshift($document->children,$tocnode);
		return $document;
	}
}

#==========================================================================
#-----------------------------[ PARSER ]----------------------------------
#==========================================================================
/*
	public methods:
	$parser = new SimpleWikiParser($text) - create instance of parser for text
	->parse() - main method, parses text, returns document object model (tree)
	->prepare($markup) - reset with new markup
	->metadata() - set with ```## arguments on first line
	->argchars($argchars = NULL) - get or set characters allowed for arguments
	->up_to($node, $kinds) - made available for callbacks, finds first
		ancestor instance of node of kind, including current node
	->parse_arguments($arguments) - utility, takes string of discreet values,
		attributes, or properties, returns object of arguments
	for debugging:
		->display_regex()
		->display_dom($dom)
*/
# see _replace($groups) below, which is the controller for the parsing process.
class SimpleWikiParser
{
	protected $_rules;
	protected $pre_escape_re;
	protected $link_re;
	protected $item_re;
	protected $cell_re;
	protected $block_re;
	protected $inline_re;
	protected $decorator_re;
	
	protected $_raw;
	protected $_curnode;
	protected $_textleafnode;
	protected $_root;
	protected $_argchars = '\\w\\s:="\'%\\\#.-'; // for decorators, notably parentheses omitted for security
	protected $_metadata; // from first line ```## arguments
	
	public function __construct($text)
	{
		$this->_set_rules();
		$this->_set_re($this->_rules);
		$this->prepare($text);
	}
	public function metadata()
	{
		return $this->_metadata;
	}
	public function prepare($text)
	{
		$this->_raw = $text;
        $this->_root = new SimpleWikiDocNode('document');
        $this->_curnode = $this->_root;        # The most recent document node
        $this->_textleafnode = NULL;           # The node to add inline characters to
		return $this;
	}
	public function argchars($argchars = NULL)
	{
		if (!is_null($argchars))
			$this->_argchars = $argchars;
		return $this->_argchars;
	}
	# set rules for parsing
	protected function _set_rules()
	{
		// the first group name of each rule, if set, is used by controller (_replace($groups)) 
		// for further processing of parsed data
		$rules = new StdClass();
		$argchars = $this->_argchars;
		#================================[ basic processing ]=================================#
		# no explicit action by user (other than include blank lines between blocks)
//		$rules->char =  '(?P<char> . )'; // slower, but allows capture or raw url's
		$rules->char =  '(?P<char> ([\\w\\s]+$|. ))'; //faster, but misses raw url's
		$rules->line = '(?P<line> ^ \\s* $ )'; # empty line that separates blocks, especially paragraphs
		$rules->text = '(?P<text>
			^(\\|:p\\s+(?P<paragraph_decorator>['.$argchars.']+?):\\|)?(?P<text_chars>.+)
			|
			(?P<text_charstream>.+) 
		)'; # text not otherwise parsed with block parsing - handed over to inline pasing
		#================================[ core markup ]===============================#
		#--------------------------------[ basic markup ]------------------------------#
		// headings
		$rules->heading = '(?P<heading>
            ^(\\|:h\\s+(?P<heading_decorator>['.$argchars.']+?):\\|)? \\s*
            (?P<heading_head>=+) \\s*
            (?P<heading_text> .*? ) \\s*
            (?P<heading_tail>=*) \\s*
            $
        )';
		// emphasis
		$rules->emph = '(?P<emph> (?<!:)\/\/|\/\/(?=\\s) )'; # there must be no : in front of the //
									# or there must be whitespace after the forwardslashes
									# - avoids italic rendering in urls with unknown protocols
		// strong
		$rules->strong = '(?P<strong> \\*\\* )';
		// linebreak
		$rules->linebreak = '(?P<break> \\\\\\\\ )';
		// horizontal rule
		$rules->separator = '(?P<separator>
            (?>
			^ \\s* ---- \\s* $ 
			)
		)';
		#--------------------------------[ links ]-------------------------------------#
		# supported protocols:
//		$proto = 'http|https|ftp|nntp|news|mailto|telnet|file|irc';
		$proto = 'http|https|mailto'; // commonly used protocols
/*		# unmarked urls... not used for performance reasons
		$rules->url =  "(?P<url>
            (?>
            (^ | (?<=\\s | [.,:;!?()\/=]))
            (?P<escaped_url>~)?
            (?P<url_target> (?P<url_proto> $proto ):\\S+? )
            ($ | (?=\\s | [,.:;!?()] (\\s | $)))
			)
		)";
*/
		# marked links...
		$rules->link = '(?P<link>
            (?>
            (%l\\s+(?P<link_decorator>['.$argchars.']+?)%)?
			\\[\\[
            (?P<link_target>.+?) \\s*
            ([|] \\s* (?P<link_text>.*?) \\s* ([|] \\s* (?P<link_title>[^|\\]}]+))? \\s*)?
            \\]\\](?!]) # allow embedded "]"
			)
		)';
		#--------------------------------[ images ]-------------------------------------#
		$rules->image = '(?P<image>
            (?>
            (%i\\s+(?P<image_decorator>['.$argchars.']+?)%)?{{
            (?P<image_target>.+?) \\s*
            ([|] \\s* (?P<image_text>.*?) \\s* ([|] \\s* (?P<image_title>[^|\\]}]+))? \\s*)?
            }}
			)
		)';
		#--------------------------------[ lists ]-------------------------------------#
		# ordered or unordered lists
		$rules->list = '(?P<list>
            ^ 
			(\\|:([uo]l|li) \\s+([\\w\\s:="\'-]+):\\|){0,2}
			[ \\t]* ([*](?=[^*\\#])|[\\#](?=[^\\#*])).* $
            ( \\n
			(\\|:([uo]l|li) \\s+([\\w\\s:="\'-]+):\\|){0,2}
			[ \\t]* [*\\#]+.* $ )*
        )'; # Matches the whole list, separate items are parsed later. The
			# list *must* start with a single bullet.

		$rules->item = '(?P<item>
            (?>
            ^
			(\\|:[uo]l\\s+(?P<list_decorator> (['.$argchars.']+?)):\\|)?
			(\\|:li\\s+(?P<item_decorator>(['.$argchars.']+)):\\|)?
			\\s*
            (?P<item_head> [\\#*]+) \\s*
            (?P<item_text> .*?)
            $
			)
        )'; # Matches single list item
		#--------------------------------[ tables ]-------------------------------------#
		# simple tables, one line per row
		$rules->table = '(?P<table>
            (?>
            ^
			(\\|:table\\s+(?P<table_decorator>(['.$argchars.']+?)):\\|)?
			(\\|:tr\\s+(?P<row_decorator>(['.$argchars.']+?)):\\|)? 
			\\s*
			(?P<table_row>
            ((\\|:(td|th)\\s+(['.$argchars.']+?):\\|)?[|](?!:[a-z]).*?)* \\s*
            [|] \\s*
			)
            $
			)
        )'; # table requires closing pipe
		# break table row into cells
		$rules->cell = '
            (\\|:(td|th)\\s+(?P<cell_decorator>['.$argchars.']+?)\\:\\|)?
			\\| \\s*
            (
                (?P<head> [=]([^|]|(?<=~)[|])+ ) |
                (?P<cell> ([^|]|(?<=~)[|])+ )
            ) \\s* 
        '; # used for preg_match in table_repl
		#================================[ escape character ]=================================#
		$rules->escape = '(?P<escape> ~ (?P<escaped_char>\\S) )'; # embedded in various regex's
		#================================[ special decorators ]===============================#
		#--------------------------------[ span decoration ]----------------------------------#
		$rules->span = '(?P<span> %(s\\s+(?P<span_decorator>['.$argchars.']+?))?% )';
		#--------------------------------[ block dividers ]-----------------------------------#
		$rules->blockdivider = '(?P<blockdivider>
            (?>
			^\\s* \\|:b \\s+(?P<blockdivider_decorator>(['.$argchars.']+?)):\\| \\s* $ 
			)
		)'; # generic block
		#===============================[ preformatted text ]=================================#
		// inline
		$rules->code = '(?P<code>
            (?>
			(%c\\s+(?P<code_decorator>['.$argchars.']+?)%)?{{{ (?P<code_text>.*?) }}} 
			)
		)';
		// block
		$rules->pre = '(?P<pre>
            (?>
            ^(\\|:pre\\s+(?P<pre_decorator>['.$argchars.']+?):\\|)?{{{ \\s* $
            (\\n)?
            (?P<pre_text>
                ([\\#]!(?P<pre_kind>\\w*?)(\\s+.*)?$)?
                (.|\\n)+?
            )
            (\\n)?
            ^}}} \\s*$
			)
        )';
		$rules->pre_escape = ' ^(?P<indent>\\s*) ~ (?P<rest> \\}\\}\\} \\s*) $';
		#================================[ advanced markup ]===============================#
		#--------------------------------[ block declarations ]------------------------------#
		$rules->blockdef = '
            (?>
			(?P<blockdef>
			^\\s*
			\\(:(?P<block_selector>\\w+?)(?P<block_id>\\d*)(\\s+(?P<block_decorator>['.$argchars.']+?))? \\s* :\\)
			\\s*?(?P<block_inline>.*) $
			(?P<block_content>((?!\\n\\s*\\(:(?P=block_selector)(?P=block_id)end\\s*:\\))\\n.*$)*)
			\\n\\s*\\(:(?P=block_selector)(?P=block_id)end\\s*:\\)\\s*$
			)
		)'; #block declarations
		#--------------------------------[ macros ]--------------------------------#
		// inline
		$rules->macro = '(?P<macro>
            (?>
			<<
            (?P<macro_name> \\w+)
            ((?P<macro_args> ['.$argchars.']*) )? \\s*
            ([|] \\s* (?P<macro_text> .+?) \\s* )?
            >>
			)
        )'; 
		// block version to prevent generation of <p> markup
		$rules->blockmacro = '(?P<blockmacro>
            (?>
			^ \\s*
			<<
            (?P<blockmacro_name> \\w+)
            ((?P<blockmacro_args> ['.$argchars.']*) )? \\s*
            ([|] \\s* (?P<blockmacro_text> .+?) \\s* )?
            >> \\s*
			$
			)
        )';
		$rules->decorator = '
			(?>(?P<variable>[\\w-]+)(?P<operator>[:=]))?	# optional attribute or property name, and operator applied
			(
				"(?P<ddelim_value>.*?)(?<!\\\)"				# double quote delimited
			|
				\'(?P<sdelim_value>.*?)(?<!\\\)\'			# single quote delimited
			|
				(?P<ndelim_value>\\S+)						# not delimited
			)
		';
		$this->_rules = $rules;
	}
	#---------------------------------------------------------------------------------------#
	#------------------------------[ set regular expressions ]------------------------------#
	#---------------------------------------------------------------------------------------#
	# combine (set) the above rules into regular expressions
	protected function _set_re($rules)
	{
		// from least to most general
		# For special case pre escaping, in creole 1.0 done with ~:
		$this->pre_escape_re = '/' . $rules->pre_escape . '/xm';
		# For sub-processing:
		$this->link_re = "/\n"
			. implode("\n|\n",
			array($rules->code, $rules->image, $rules->strong, 
				$rules->emph, $rules->span, $rules->linebreak, 
				$rules->escape, $rules->char))
			. "\n/x"; # for link captions
		$this->image_re = "/\n"
			. implode("\n|\n",
				array($rules->link, $rules->code, $rules->strong, 
				$rules->emph, $rules->span, $rules->linebreak, 
				$rules->escape, $rules->char))
			. "\n/x"; # for image captions
		$this->item_re = '/' . $rules->item . '/xm'; # for list items
		$this->cell_re = '/' . $rules->cell . '/x'; # for table cells
/*		$this->cellcontents_re = "/\n" // use full inline_re instead
			. implode("\n|\n",
				array($rules->link, $rules->macro, $rules->code, 
				$rules->image, $rules->escape, $rules->char))
			. "\n/x";*/
		# For inline elements:
		$this->inline_re = "/\n" 
			. implode("\n|\n", 
//				array($rules->link, $rules->url, $rules->macro, // url's not used for performace reasons
				array($rules->link, $rules->macro,
				$rules->code, $rules->image, $rules->strong, $rules->emph, 
				$rules->span, $rules->linebreak, $rules->escape, $rules->char))
			. "\n/x";
		$this->tablerow_setaside_re =  "/\n" 
			. implode("\n|\n", array($rules->link, $rules->macro,$rules->code,$rules->image))
			. "\n/x";
		# For block elements:
		$this->block_re = "/\n" 
			. implode("\n|\n",
				array($rules->line, $rules->blockdef, $rules->heading, 
				$rules->separator, $rules->blockdivider, $rules->blockmacro,
				$rules->pre, $rules->list, $rules->table, $rules->text)) 
			. "\n/xm";
		$this->decorator_re = '/' . $rules->decorator . '/x';
	}
	#---------------------[ process initiation ]--------------------------#
	// structures for setting aside preformatted data before reduction of newline characters
	protected $_pre_markers = array();
	protected $_pre_text = array();
	protected $_pre_count = 0;
	// mark locations of preformatted data; set aside preformatted data
	protected function add_pre_markers($groups)
	{
		$this->_pre_text[] = $groups['pre'];
		$this->_pre_count++;
		$marker = '{{{' . chr(255). $this->_pre_count . '}}}';
		$this->_pre_markers[] = '/{{\\{' . chr(255) . $this->_pre_count . '\\}}}/';
		return $marker;
	}
	protected function reduce_newlines($raw)
	{
		if (preg_match('/\A```##(.*$)/m',$raw,$matches))
		{
			$arguments = trim($matches[1]);
			$this->_metadata = $this->parse_arguments($arguments);
		}
		$raw = "\n".$raw."\n"; // in case there is comment on first line, lookahead on last
		# remove comments
		$raw = preg_replace('/\\n```.*$/m','',$raw);
		# remove line continuations
		$raw = preg_replace('/\\n``/','',$raw);
		# set aside preformatted blocks
		$raw = preg_replace_callback('/'.$this->_rules->pre .'/xm',array($this,'add_pre_markers'),$raw);
		# trim lines, and remove unnecessary newlines
		$raw = preg_replace('/^[ \\t]+|[ \\t]+$/m','',$raw); // trim all lines
		$raw = preg_replace('/((\\w|\\\\)\\n(\\w))+/m','$2 $3',$raw); // replace single line breaks with spaces
		# restore preformatted blocks
		$raw = preg_replace($this->_pre_markers,$this->_pre_text,$raw);
		return $raw;
	}
    public function parse() // initiate parsing
	{
		# try to clean $raw of unnecessary newlines
		$raw = $this->reduce_newlines($this->_raw);
        # parse the text given as $this->_raw...
        $this->_parse_block($raw);
		#...and return DOM tree.
        return $this->_root;
	}
    protected function _parse_block($raw)
	{
        # Recognize block elements.
        preg_replace_callback($this->block_re, array($this,'_replace'), $raw);
	}
    protected function _parse_inline($raw)
	{
        # Recognize inline elements inside blocks.
        preg_replace_callback($this->inline_re, array($this,'_replace'), $raw);
	}
	#---------------------[ process control ]--------------------------#
    protected function _replace($groups) // controller
	{
        # Invoke appropriate _*_repl method. Called for every matched group.
		foreach ($groups as $name => $text)
		{
			if ((!is_int($name)) and ($text != ''))
			{
				$replace = "_{$name}_repl";
				$this->$replace($groups);
				return;
			}
		}
		# special case: pick up empty line for block boundary
		$keys = array_keys($groups);
		$name = 'line';
		if ($keys[count($keys)-2]==$name) // last name in key array indicates returned as found
		{
			$replace = "_{$name}_repl";
			$this->$replace($groups);
			return;
		}
	}
	// common argument structure for decorators and block declarations
	// returns object
	public function parse_arguments($decorator) 
	{
		$arguments = new StdClass();
		$arguments->classes = array();
		$arguments->properties = array();
		$arguments->attributes = array();
		$terms = array();
		preg_match_all($this->decorator_re, $decorator, $terms, PREG_SET_ORDER); // returns terms
		foreach($terms as $term) 
		{
			$variable = $term['variable'];
			$operator = $term['operator'];
			$value = 
				@$term['ddelim_value'] 
				. @$term['sdelim_value'] 
				. @$term['ndelim_value']; // only one will have succeeded
			switch ($operator)
			{
				case '=':
					$arguments->attributes[$variable] = $value;
					break;
				case ':':
					$arguments->properties[$variable] = $value;
					break;
				default:
					$arguments->classes[] = $value;
					break;
			}
		}
		return $arguments;
	}
	// parse arguments from string to structure
	protected function set_decorator($node,$decorator)
	{
		$node->argumentstring = $decorator;
		$node->decorator = $this->parse_arguments($decorator);
		return $node;
	}
	#------------------------------------------------------------------------------#
	#----------------------------[ dom creation ]----------------------------------#
	#------------------------------------------------------------------------------#
    # The _*_repl methods called for matches in regex by 
	# controller (_replace($groups)) where $groups = returned regex (parenthesized) groups
	#=========================[ basic processing ]=================================#
    protected function _char_repl($groups) // can create text leaf node
	{
		# character by character added to text stream
		$char = $this->get_array_value($groups,'char', '');
        if (is_null($this->_textleafnode))
            $this->_textleafnode = new SimpleWikiDocNode('text', $this->_curnode);
        $this->_textleafnode->content .= $char;
	}
    protected function _escape_repl($groups)
	{
		$char = $this->get_array_value($groups,'escaped_char', '');
        if (is_null($this->_textleafnode))
            $this->_textleafnode = new SimpleWikiDocNode('text', $this->_curnode);
        $this->_textleafnode->content .= $char;
	}
    protected function _line_repl($groups)
	{
		# triggers new block
        $this->_curnode = $this->up_to($this->_curnode, array('document','blockdef'));
	}
    protected function _text_repl($groups) // can create paragraph for new text
	{
		# text not otherwise classified, triggers creation of paragraph for new set
        $text = $this->get_array_value($groups,'text_chars','') . $this->get_array_value($groups,'text_charstream','');
		$decorator = $this->get_array_value($groups,'paragraph_decorator','');
        if (in_array($this->_curnode->kind, 
			array('table', 'table_row', 'bullet_list', 'number_list'))) // text cannot exist in these blocks
		{
            $this->_curnode = $this->up_to($this->_curnode,
                array('document','blockdef'));
		}
        if (in_array($this->_curnode->kind, array('document','blockdef')))
		{
            $node = $this->_curnode = new SimpleWikiDocNode('paragraph', $this->_curnode);
			if ($decorator != '') $node = $this->set_decorator($node,$decorator);
		}
        else
            $text = ' ' . $text;
        $this->_parse_inline($text);
        $this->_textleafnode = NULL;
	}
	#================================[ core markup ]===============================#
	#--------------------------------[ basic markup ]------------------------------#
    protected function _heading_repl($groups)
	{
		# headings
		$headtext = $this->get_array_value($groups,'heading_text','');
		$headhead = $this->get_array_value($groups,'heading_head','');
		$decorator = $this->get_array_value($groups,'heading_decorator','');
		
        $this->_curnode = $this->up_to($this->_curnode, array('document','blockdef'));
		
        $node = new SimpleWikiDocNode('heading',$this->_curnode);
        $node->level = strlen($headhead);
		if ($decorator != '') $node = $this->set_decorator($node,$decorator);

        $parent = $this->_curnode;
        $this->_curnode = $node;
        $this->_textleafnode = NULL;

        $this->_parse_inline($headtext);

        $this->_curnode = $parent;
        $this->_textleafnode = NULL;
	}
    protected function _emph_repl($groups)
	{
		# emphasis
        if ($this->_curnode->kind != 'emphasis')
            $this->_curnode = new SimpleWikiDocNode('emphasis', $this->_curnode);
        else
		{
			if (!empty($this->_curnode->parent))
				$this->_curnode = $this->_curnode->parent;
		}
        $this->_textleafnode = NULL;
	}
    protected function _strong_repl($groups)
	{
		# strong
        if ($this->_curnode->kind != 'strong')
            $this->_curnode = new SimpleWikiDocNode('strong', $this->_curnode);
        else
		{
			if (!empty($this->_curnode->parent))
				$this->_curnode = $this->_curnode->parent;
		}
        $this->_textleafnode = NULL;
	}
    protected function _break_repl($groups)
	{
		# line break
        new SimpleWikiDocNode('break', $this->_curnode);
        $this->_textleafnode = NULL;
	}
    protected function _separator_repl($groups)
	{
        $this->_curnode = $this->up_to($this->_curnode, array('document','blockdef'));
        new SimpleWikiDocNode('separator', $this->_curnode);
	}
	#--------------------------------[ links ]-------------------------------------#
/* not used for performance reasons
    protected function _url_repl($groups)
	{
        # Handle raw urls in text.
        $target = $this->get_array_value($groups,'url_target','');
        if (empty($groups['escaped_url']))
		{
            # this url is NOT escaped
            $node = new SimpleWikiDocNode('url', $this->_curnode, $target);
            new SimpleWikiDocNode('text', $node, $target);
            $this->_textleafnode = NULL;
		}
        else
		{
            # this url is escaped, we render it as text
            if ($this->_textleafnode == NULL)
                $this->_textleafnode = new SimpleWikiDocNode('text', $this->_curnode,'');
            $this->_textleafnode->content .= $target;
		}
	}
*/
    protected function _link_repl($groups)
	{
        # Handle all kinds of links.
        $target = trim($this->get_array_value($groups,'link_target', ''));
        $text = trim($this->get_array_value($groups,'link_text',''));
		$title = trim($this->get_array_value($groups,'link_title',''));
		$decorator = trim($this->get_array_value($groups,'link_decorator', ''));
		
		$node =  new SimpleWikiDocNode('link', $this->_curnode,$target);
		if ($decorator != '') $node = $this->set_decorator($node,$decorator);
		if ($title != '') $node->title = $title;

        $parent = $this->_curnode;
        $this->_curnode = $node;
        $this->_textleafnode = NULL;

        preg_replace_callback($this->link_re, array($this,'_replace'), $text);

        $this->_curnode = $parent;
        $this->_textleafnode = NULL;
	}
	#--------------------------------[ images ]-------------------------------------#
	protected function _image_repl($groups)
	{
        # Handles images included in the page.
        $target = trim($this->get_array_value($groups,'image_target',''));
        $text = trim($this->get_array_value($groups,'image_text', ''));
		$title = trim($this->get_array_value($groups,'image_title',''));
		$decorator = trim($this->get_array_value($groups,'image_decorator', ''));
		
        $node = new SimpleWikiDocNode('image', $this->_curnode, $target);
		if ($decorator != '') $node = $this->set_decorator($node,$decorator);
		if ($title != '') $node->title = $title;

        $parent = $this->_curnode;
        $this->_curnode = $node;
        $this->_textleafnode = NULL;

        preg_replace_callback($this->image_re, array($this,'_replace'), $text);

        $this->_curnode = $parent;
        $this->_textleafnode = NULL;
	}
	#--------------------------------[ lists ]-------------------------------------#
    protected function _list_repl($groups)
	{
		# collect list markup, detail processing by item
        $text = $this->get_array_value($groups,'list','');
        preg_replace_callback($this->item_re,array($this,'_replace'), $text);
	}
    protected function _item_repl($groups)
	{
		# list item
        $bullet = $this->get_array_value($groups,'item_head','');
        $text = $this->get_array_value($groups,'item_text','');
		$listdecorator = $this->get_array_value($groups,'list_decorator','');
		$itemdecorator = $this->get_array_value($groups,'item_decorator','');
        if ($bullet{0} == '#')
            $kind = 'number_list';
        else
            $kind = 'bullet_list';
        $level = strlen($bullet);
        $list = $this->_curnode;
        # Find a list of the same kind and level up the tree
        while 
		(
			$list // searching an existing node
			and ! // this is a not a list of the same level...
			(
				in_array($list->kind, array('number_list', 'bullet_list')) 
				and $list->level == $level
			)
			and ! // this is not a block
			(
				in_array($list->kind, array('document','blockdef'))
			)
		) // keep looking
		{
            $list = $list->parent;
		}
        if ($list and ($list->kind == $kind)) // found a match
            $this->_curnode = $list;
        else
		{
            # Create a new level of list
            $this->_curnode = $this->up_to($this->_curnode,
                array('list_item', 'document','blockdef'));
            $node = $this->_curnode = new SimpleWikiDocNode($kind, $this->_curnode);
			if ($listdecorator != '') $node = $this->set_decorator($node,$listdecorator);
            $this->_curnode->level = $level;
		}
        $node = $this->_curnode = new SimpleWikiDocNode('list_item', $this->_curnode);
		if ($itemdecorator != '') $node = $this->set_decorator($node,$itemdecorator);
		$this->_textleafnode = NULL;
		
        $this->_parse_inline($text);
        $this->_textleafnode = NULL;
	}
	#--------------------------------[ tables ]-------------------------------------#
	// structure to set aside row contents before parsing row structure itself
	protected $_tablerow_markers = array();
	protected $_tablerow_text = array();
	protected $_tablerow_count = 0;
	// contents marked to be replaced after row structure parsed
	protected function add_tablerow_markers($groups)
	{
		$value = 
			(
				@$groups['link']?
					@$groups['link']:(@$groups['macro']?
						@$groups['macro']:(@$groups['code']?
							@$groups['code']:@$groups['image'])));
		$this->_tablerow_text[] = $value;
		$this->_tablerow_count++;
		$marker = '{{{' . chr(255). $this->_tablerow_count . '}}}';
		$this->_tablerow_markers[] = '/{{\\{' . chr(255) . $this->_tablerow_count . '\\}}}/';
		return $marker;
	}
    protected function _table_repl($groups)
	{
		# process a table row (any line beginning with '|')
        $row = trim($this->get_array_value($groups,'table_row', ''));
		$tdecorator = trim($this->get_array_value($groups,'table_decorator', ''));
		$rdecorator = trim($this->get_array_value($groups,'row_decorator', ''));
		
		// set aside links and preformats and macros and images
		$row = preg_replace_callback(
			$this->tablerow_setaside_re,
			array($this,'add_tablerow_markers'),$row);
		
		$row = preg_replace('/((?<!:)[|](?=[|]))/','| ',$row); // ensure content for every cell

        $this->_curnode = $this->up_to($this->_curnode, array('table','document','blockdef'));
		$newtable = FALSE;
        if ($this->_curnode->kind != 'table')
		{
            $this->_curnode = new SimpleWikiDocNode('table', $this->_curnode);
			$newtable = TRUE;
		}
        $tb = $this->_curnode;
		if ($tdecorator != '') $tb = $this->set_decorator($tb,$tdecorator);

        $text = '';
		$isheader = FALSE;
		$result = preg_match_all($this->cell_re, $row, $cells,PREG_SET_ORDER);
		if ($newtable)
		{
			foreach ($cells as $cellgroups)
			{
				if ($cellgroups['head'] != '')
				{
					$isheader = TRUE;
					break;
				}
			}
		}
		if ($isheader)
			$tr = new SimpleWikiDocNode('table_headrow', $tb);
		else
			$tr = new SimpleWikiDocNode('table_row', $tb);
		if ($rdecorator != '') $tr = $this->set_decorator($tr,$rdecorator);
		// now have table and row, can process cells
        $this->_textleafnode = NULL;
		foreach ($cells as $cellgroups)
		{
			$cell = $this->get_array_value($cellgroups,'cell','');
			$head = $this->get_array_value($cellgroups,'head','');
			$decorator = $this->get_array_value($cellgroups,'cell_decorator','');
            if ($head) 
			{
				$cell = trim($head,'=');
                $node = $this->_curnode = new SimpleWikiDocNode('table_headcell', $tr);
			}
			else
			{
                $node = $this->_curnode = new SimpleWikiDocNode('table_cell', $tr);
			}
            $this->_textleafnode = NULL;
			if ($decorator != '') $node = $this->set_decorator($node,$decorator);
			// restore links and preformats and macros and images
			$cell = preg_replace($this->_tablerow_markers,$this->_tablerow_text,$cell);
			preg_replace_callback($this->inline_re, array($this,'_replace'), $cell);
		}
        $this->_curnode = $tb;
        $this->_textleafnode = NULL;
		
		$this->_tablerow_markers = array();
		$this->_tablerow_text = array();
		$this->_tablerow_count = 0;
	}
	#================================[ special decorators ]=============================#
	#--------------------------------[ span decoration ]--------------------------------#
    protected function _span_repl($groups)
	{
		# span
		$decorator = $this->get_array_value($groups,'span_decorator','');
        if ($decorator != '') // new span
		{
            $node = $this->_curnode = new SimpleWikiDocNode('span', $this->_curnode);
			$node = $this->set_decorator($node,$decorator);
			$this->_textleafnode = NULL;
		}
        elseif ($this->_curnode->kind == 'span') // closing existing span
		{
			if (!empty($this->_curnode->parent))
			{
				$this->_curnode = $this->_curnode->parent;
				$this->_textleafnode = NULL;
			}
		}
		else // error, return text
		{
			if ($this->_textleafnode == NULL)
				$this->_textleafnode = new SimpleWikiDocNode('text', $this->_curnode);
			$this->_textleafnode->content .= $groups['span'];
		}
	}
	#--------------------------------[ block dividers ]--------------------------------#
    protected function _blockdivider_repl($groups)
	{
		# empty block acting as block divider
		$decorator = $this->get_array_value($groups,'blockdivider_decorator','');
        $this->_curnode = $this->up_to($this->_curnode, array('document','blockdef'));
        $node = new SimpleWikiDocNode('blockdivider', $this->_curnode);
		if ($decorator != '') $node = $this->set_decorator($node,$decorator);
	}
	#============================[ preformatted text ]=================================#
    protected function _code_repl($groups)
	{
		# preformatted inline text
		$codetext = $this->get_array_value($groups,'code_text', '');
		$decorator = trim($this->get_array_value($groups,'code_decorator', ''));
		
        $node = new SimpleWikiDocNode('code', $this->_curnode, trim($codetext));
		if ($decorator != '') $node = $this->set_decorator($node,$decorator);
        $this->_textleafnode = NULL;
	}
    protected function _pre_repl($groups)
	{
		# process preformatted text
        $kind = $this->get_array_value($groups,'pre_kind', NULL);
        $text = $this->get_array_value($groups,'pre_text', '');
		$decorator = $this->get_array_value($groups,'pre_decorator','');
		
        $this->_curnode = $this->up_to($this->_curnode, array('document','blockdef'));
        $text = preg_replace_callback($this->pre_escape_re,array($this,'remove_tilde'), $text);
        $node = new SimpleWikiDocNode('preformatted', $this->_curnode, $text);
        $node->section = $kind?$kind:'';
		if ($decorator != '') $node = $this->set_decorator($node,$decorator);
        $this->_textleafnode = NULL;
	}
    private function remove_tilde($groups)
	{
		# used in pre processing
        return $groups['indent'] . $groups['rest'];
	}
	#================================[ advanced markup ]===============================#
	#--------------------------------[ block declarations ]------------------------------#
	protected function _blockdef_repl($groups)
	{
		# block definitions
		$name = $this->get_array_value($groups,'block_selector','');
		$content = $this->get_array_value($groups,'block_content','');
		$decorator = $this->get_array_value($groups,'block_decorator','');
		$inline = $this->get_array_value($groups,'block_inline','');
		$blockid = $this->get_array_value($groups,'block_id','');
		
        $container = $this->_curnode = $this->up_to($this->_curnode,
            array('document','blockdef','list_item'));
        $node = $this->_curnode = new SimpleWikiDocNode('blockdef', $container);
		$node->blocktag = $name;
		$node->blockid = $blockid;
		if ($decorator != '') $node = $this->set_decorator($node,$decorator);
		
		$this->_textleafnode = NULL;
        if ($inline != '') $this->_parse_inline($inline);
		$this->_textleafnode = NULL;
        if ($content != '') $this->_parse_block($content);
		$this->_curnode = $container;
        $this->_textleafnode = NULL;
		
	}
	#-----------------------------------[ macros ]-------------------------------------#
	protected function _macro_repl($groups)
	{
        # Handles macros using the placeholder syntax.
        $name = $this->get_array_value($groups,'macro_name', '');
        $text = trim($this->get_array_value($groups,'macro_text',''));
		$decorator = $this->get_array_value($groups,'macro_args', '');

		$container = $this->_curnode;
        $node = new SimpleWikiDocNode('macro', $container);
		$node->macroname = $name;

        if ($decorator != '') $node = $this->set_decorator($node,$decorator);
		if ($text != '')
		{
			$node->text = $text;
			$this->_curnode = $node;
			$this->_textleafnode = NULL;
			$this->_parse_inline($text);
			$this->_curnode = $container;
		}
        $this->_textleafnode = NULL;
	}
	protected function _blockmacro_repl($groups)
	{
        # Handles macros using the placeholder syntax. block version
        $name = $this->get_array_value($groups,'blockmacro_name', '');
        $text = trim($this->get_array_value($groups,'blockmacro_text',''));
		$decorator = $this->get_array_value($groups,'blockmacro_args', '');
		
        $container = $this->_curnode = $this->up_to($this->_curnode, array('document','blockdef')); // different from macro
        $node = new SimpleWikiDocNode('macro', $this->_curnode);
		$node->macroname = $name;
        if ($decorator != '') $node = $this->set_decorator($node,$decorator);
		if ($text != '')
		{
			$node->text = $text;
			$this->_curnode = $node;
			$this->_textleafnode = NULL;
			$this->_parse_inline($text);
			$this->_curnode = $container;
		}
        $this->_textleafnode = NULL;
	}
	#------------------------------------------------------------------------------#
	#---------------------------[ utilities ]--------------------------------------#
	#------------------------------------------------------------------------------#
    public function up_to($node, $kinds) // public as can be used by registered callbacks
	{
        /*
        Look up the tree (starting with $node) to the first occurence
        of one of the listed kinds of nodes or root.
		If $node is in the list then the current node is returned.
        */
//        while ((!is_null($node->parent)) and (!in_array($node->kind,$kinds)))
        while ((!empty($node->parent)) and (!in_array($node->kind,$kinds)))
		{
            $node = $node->parent;
		}
        return $node;
	}
	protected function get_array_value($array,$index,$default)
	{
		# return default if array value not set
		return isset($array[$index])?$array[$index]:$default;
	}
	#------------------------------------------------------------------------------#
	#---------------------------[ debug functions ]--------------------------------#
	#------------------------------------------------------------------------------#
	public function display_regex() // for debug
	{
		echo 'BLOCK_RE ';
		var_dump($this->block_re);
		echo 'INLINE_RE ';
		var_dump($this->inline_re);
		echo 'LINK_RE ';
		var_dump($this->link_re);
		echo 'ITEM_RE ';
		var_dump($this->image_re);
		echo 'ITEM_RE ';
		var_dump($this->item_re);
		echo 'CELL_RE ';
		var_dump($this->cell_re);
/*		echo 'CELLCONTENTS_RE ';
		var_dump($this->cellcontents_re);*/
		echo 'PRE_ESCAPE_RE ';
		var_dump($this->pre_escape_re);
		echo 'DECORATOR_RE ';
		var_dump($this->decorator_re);
	}
	public function display_dom($root) // for debug
	{
		$count = 1;
		$rootarray = array();
		$count += $this->display_dom_add_child($root,$rootarray);
		$rootarray = $rootarray[0];
		print_r($rootarray);
		return $count;
	}
	protected function display_dom_add_child($node,&$childarray) // for debug
	{
		$nodearray = $node->get_display_list();
		$children = $node->children;
		$count = 0;
		if (!empty($children))
		{
			$nodearray['children'] = array();
			foreach ($children as $child)
				$count+= $this->display_dom_add_child($child,$nodearray['children']);
		}
		$childarray[] = $nodearray;
		return count($children) + $count;
	}
}

#==========================================================================#
#--------------------------[ DOCUMENT NODE ]-------------------------------#
#==========================================================================#
// the parser creates a document tree (document object model) consisting of these nodes
class SimpleWikiDocNode 
{
    # A node in the document tree.
	public $children;
	public $parent;
	public $kind;

    public function __construct($kind='', $parent=NULL, $content=NULL)
	{
        $this->children = array();
        $this->kind = $kind;
        $this->parent = $parent;
        if (!is_null($content)) $this->content = $content;
        if (!empty($parent))
            $parent->child_append($this);
	}
	protected function child_append($child)
	{
		$this->children[] = $child;
	}
	public function get_display_list() // for debug
	{
		$array = (array) $this;
		$retarr = array();
		foreach ($array as $property => $value)
		{
			if (($property != 'children') and ($property != 'parent'))
				$retarray[$property] = $value;
		}
		return $retarray;
	}
}

#==========================================================================#
#-----------------------------[ EMITTER ]----------------------------------#
#==========================================================================#
/*
public methods:
$emitter = new SimpleWikiEmitter() - create new instance of emitter
->emit($dom) - generate html from the passed document object model
->emit_children($node) - useful for registered method classes, macros and events
->emit_node_text($node) // text only, no html or other markup, helpful for registrants
->symlinks() - returns registered symlinks
->symlink_handler() - returns registered symlink handler
->blocktags($blocktag=$NULL) - get or set collection of block declarations recognized by emitter
->register_events($callbacks)
->register_symlinks($symlinks)
->register_symlink_handler($handler) - default handler for symlinks not registered
->register_class_callouts($callouts) - typically called by SimpleWiki (as facade)
->register_macro_callouts($callouts) - typically called by SimpleWiki (as facade)
*/
// generates the HTML; other emitters could be substituted
class SimpleWikiEmitter
{
	protected $_dom;
	protected $_rules;
	protected $_class_callouts = array();
	protected $_macro_callouts = array();
	protected $_symlinks = array();
	protected $_symlink_handler;
	protected $_events = array();
	protected $_blocktags = // could be replaced or changed by client
		array
		(
			'div', 'blockquote', # division, blockquote
			'table', 'thead', 'tbody', 'tr', 'td', 'th', 'tfoot', 'caption', # table
			'ul', 'ol', 'li', 'dl', 'dt', 'dd' # lists
		);
	protected $addr_re;
	
	public function __construct()
	{
		$this->set_rules();
		$this->set_re($this->_rules);
	}
	public function emit($dom) // main method
	{
		$this->_dom = $dom;
		return $this->emit_node($dom);
	}
	public function symlinks()
	{
		return $this->_symlinks;
	}
	public function symlink_handler()
	{
		return $this->_symlink_handler;
	}
	public function blocktags($blocktaglist = NULL)
	{
		if (!is_null($blocktaglist))
			$this->_blocktags = $blocktaglist;
		return $this->_blocktags;
	}
	#========================[ callout handling ]=======================================#
	// clients register callouts for classes, macros, symlinks, and start/finish events
	#---------------------------[register callouts]-------------------------------------#
	# all callbacks are passed nodes
	// events are published - all registered callbacks are notified, including multiple callbacks per event.
	// ['event']['methodref']
	public function register_events($callbacks)
	{
		$events = $this->_events;
		foreach ($callbacks as $eventname => $callback)
		{
			if (!isset($events[$eventname]))
			{
				$events[$eventname] = array();
			}
			$events[$eventname][] = $callback;
		}
		$this->_events = $events;
	}
	public function register_symlinks($symlinks)
	{
		$symlinklist = $this->_symlinks;
		foreach ($symlinks as $symlink => $value)
		{
			$symlinklist[$symlink] = $value;
		}
		$this->_symlinks = $symlinklist;
	}
	public function register_symlink_handler($handler)
	{
		$this->_symlink_handler = $handler;
	}
	// one callback per kind class
	public function register_class_callouts($callouts) 
	{
		// ['nodekind']['class']=>$methodref
		$calloutslist = $this->_class_callouts;
		foreach ($callouts as $nodekind => $classcallouts)
		{
			if (empty($calloutlist[$nodekind]))
			{
				$calloutlist[$nodekind] = $callouts[$nodekind];
			}
			else
			{
				$classcallouts = $callouts[$nodekind];
				foreach($classcallouts as $class =>$methodref)
				{
					$calloutlist[$nodekind][$class] = $methodref;
				}
			}
		}
		$this->_class_callouts = $calloutlist;
	}
	// one callback per macro
	public function register_macro_callouts($callouts)
	{
		// ['macroname'][$methodref]
		$calloutslist = $this->_macro_callouts;
		foreach ($callouts as $macroname => $methodref)
		{
			$calloutlist[$macroname] = $methodref;
		}
		$this->_macro_callouts = $calloutlist;
	}
	#---------------------------[trigger callouts]-------------------------------------#
	// triggered from prepare_link_node
	protected function expand_symlink($node)
	{
		$symlinks = $this->_symlinks;
		if (isset($symlinks[$node->symlink]))
			$node->path = $symlinks[$node->symlink];
		elseif (isset($this->_symlink_handler))
			$node = call_user_func($this->_symlink_handler,$node);
		else
			$node->path = $node->symlink . ':'; // stub
		return $node;
	}
	// triggered from prepare_macro
	protected function call_macro($node)
	{
		$node->processed = FALSE;
		$callbacks = $this->_macro_callouts;
		if (isset($callbacks[$node->macroname]))
		{
			$node = call_user_func($callbacks[$node->macroname],$node);
			$node->processed = TRUE;
		}
		return $node;
	}
	// triggered from prepare_node
	protected function call_classes($node)
	{
		$callbacks = @$this->_class_callouts[$node->kind];
		if (!empty($callbacks)) 
		{
			$classes = @$node->decorator->classes;
			if (!empty($classes))
			{
				foreach ($classes as $class)
				{
					$callback = @$callbacks[$class];
					if ($callback)
					{
						$node = call_user_func($callback,$node);
					}
				}
			}
		}
		return $node;
	}
	protected function call_event($node,$event)
	{
		$events = @$this->_events[$event];
		if (!empty($events))
		{
			foreach ($events as $callback)
			{
				$node = call_user_func($callback,$node);
			}
		}
		return $node;
	}
	#----------------------------------[ init ]-----------------------------------------#
	protected function set_rules()
	{
		$rules = new StdClass();
		$proto = 'http|https|ftp|nntp|news|mailto|telnet|file|irc';
		$rules->extern = "(?P<external_address>(?P<external_proto>$proto)
			(?P<external_selector>:.*))";
		$rules->symlink = '
            (?P<internal_address> (?P<symlink>[A-Z][a-zA-Z-]+) :
            (?P<internal_selector> .* ))
        ';
		$rules->anchor = '
			(?P<anchor>\\#[a-zA-Z][\\w-]*)
		';
		$this->_rules = $rules;
	}
	protected function set_re($rules)
	{
		$this->link_re = '/' . implode('|',array($rules->extern,$rules->symlink,$rules->anchor)) . '/x';
		$this->addr_re =  '/' . implode('|',array($rules->extern,$rules->symlink)) . '/x';
	}
	#--------------------------------[ utilities ]---------------------------------------#
	protected function get_value($value,$default)
	{
		return isset($value)? $value: $default;
	}
	protected function emit_node($node) // controller
	{
		$emit = $node->kind . '_emit';
		return $this->$emit($node);
	}
	public function emit_children($node)
	{
		if (empty($node->children)) return '';
		$children = $node->children;
		$childoutput = array();
		foreach ($children as $child)
		{
			$childoutput[] = $this->emit_node($child);
		}
		return implode('',$childoutput);
	}
	// text only, no html or other markup
	public function emit_node_text($node)
	{
		if ($node->kind == 'text')
			return $node->content;
		else
			return $this->emit_children_text($node);
	}
	protected function emit_children_text($node)
	{
		if (empty($node->children)) return '';
		$children = $node->children;
		$childoutput = array();
		foreach ($children as $child)
		{
			$childoutput[] = $this->emit_node_text($child);
		}
		return implode(' ',$childoutput);
	}
	// interpret address, inlcuding symlink; prepare src, alt, title attributes
	protected function prepare_image_node($node)
	{
		if (@$node->symlink)
		{
			$node = $this->expand_symlink($node);
			$node->decorator->attributes['src'] = 
				$node->path . $node->internalselector;
		}
		else
		{
			$node->decorator->attributes['src'] = 
				@$node->externaladdress;
		}
		if ($node->caption) 
			$node->decorator->attributes['alt'] = htmlspecialchars($node->caption);
		elseif ($node->content) 
			$node->decorator->attributes['alt'] = htmlspecialchars($node->content);
			
		if (@$node->title) $node->decorator->attributes['title'] = $node->title;
		$node = $this->prepare_node($node);
		return $node;
	}
	// identify anchor for special handling
	// prepare attributes - name, href, title
	protected function prepare_link_node($node)
	{
		$attributename = 'href';
		if (@$node->anchor)
		{
			if (!@$node->caption) 
			{
				$attributename = 'name';
				$node->anchor = substr($node->anchor,1);
				$node->decorator->attributes[$attributename] = $node->anchor;
			}
			else
			{
				$node->symlink = 'Anchor';
				$node = $this->expand_symlink($node);
				$node->decorator->attributes[$attributename] = $node->path . $node->anchor;
			}
		}
		elseif (@$node->symlink)
		{
			$node = $this->expand_symlink($node);
			$node->decorator->attributes[$attributename] = 
				$node->path . @$node->internalselector;
		}
		else
		{
			$node->decorator->attributes[$attributename] = 
				@$node->externaladdress;
		}
		if (@$node->title) $node->decorator->attributes['title'] = $node->title;
		$node = $this->prepare_node($node);
		return $node;
	}
	// trigger callouts; prepare output property
	protected function prepare_macro($node)
	{
		$node->output = '';
		$this->call_macro($node);
		if (($node->output == '') and ($node->caption != ''))
		{
			$node->output = $node->caption;
		}
		return $node;
	}
	// trigger class callouts; prepare attributes, classes, and styles for HTML
	//	by combining into single attribute array
	protected function prepare_node($node)
	{
		# trigger callouts
		$node = $this->call_classes($node);
		# convert input decorator attributes, values, and properties into html attributes
		$attributes = array();
		// attributes
		$attr = $this->get_value(@$node->decorator->attributes,array());
		foreach ($attr as $key => $value)
		{
			$attributes[] = $key . '="' . preg_replace('/"/','\\"',$value) . '"';
		}
		// classes
		$values = array();
		$values = $this->get_value(@$node->decorator->classes,array());
		$classes = preg_replace('/"/','\\"',implode(' ',$values)); // escape embedded double quotes
		if (!empty($classes)) $attributes[]='class="' . $classes . '"';
		// styles
		$properties = array();
		$properties = $this->get_value(@$node->decorator->properties,array());
		$styles = array();
		foreach ($properties as $key => $value)
		{
			$styles[] = $key . ':' . $value;
		}
		if (!empty($styles))
		{
			$style = implode(';',preg_replace('/"/','\\"',$styles)); // escape embedded double quotes
			$attributes[] = 'style="' . $style . '"';
		}
		if (!empty($attributes)) $node->prefix .= ' ';
		$node->attributes = $attributes;
		return $node;
	}
	#------------------------------------------------------------------------------#
	#-----------------------------[ node emitters ]--------------------------------#
	#------------------------------------------------------------------------------#
	#==============================[ document ]====================================#
	protected function document_emit($node)
	{
		// anticipate event calls
		$node->prefix = '';
		$node->postfix = '';
		$node = $this->call_event($node,'onemit');
		$node->infix = $this->emit_children($node);
		$node = $this->call_event($node,'onafteremit');
		return $node->prefix . $node->infix . $node->postfix;
	}
	#=========================[ basic processing ]=================================#
	protected function paragraph_emit($node) // b decorator "p"
	{
		$node->prefix = "\n<p";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "</p>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function text_emit($node)
	{
		return htmlspecialchars($node->content);
	}
	#================================[ core markup ]===============================#
	#--------------------------------[ basic markup ]------------------------------#
	protected function heading_emit($node) // b decorator "h"
	{
		$node->prefix = "\n<h" . $node->level;
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "</h". $node->level . ">";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function emphasis_emit($node)
	{
		return "<em>" . $this->emit_children($node) . "</em>";
	}
	protected function strong_emit($node)
	{
		return "<strong>" . $this->emit_children($node) . "</strong>";
	}
	protected function break_emit($node)
	{
		return "<br />\n";
	}
	protected function separator_emit($node)
	{
		return "\n<hr />";
	}
	#--------------------------------[ links ]-------------------------------------#
	// raw url not used for performance reasons
/*	protected function url_emit($node) # not used for performance reasons
	{
		$node->caption = $this->emit_children($node);
		// also available: $node->title
		$address = $node->content;
		$matches = array();
		if (preg_match($this->addr_re,$address,$matches))
		{
			@$node->internaladdress = $matches['internal_address'];
			@$node->symlink = $matches['symlink'];
			@$node->internalselector = $matches['internal_selector'];
			
			@$node->externaladdress = $matches['external_address'];
			@$node->externalprotocol = $matches['external_proto'];
			@$node->externalselector = $matches['external_selector'];
		}
		$node->prefix = "<a";
		$node->prefixtail = ">";
		$node->infix = $node->caption;
		$node->postfix = "</a>";
		$node = $this->prepare_link_node($node); 
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
*/
	protected function link_emit($node) // i decorator "a"
	{
		$node->caption = $this->emit_children($node);
		// also available: $node->title
		$address = $node->content;
		$matches = array();
		if (preg_match($this->link_re,$address,$matches))
		{
			@$node->anchor = $matches['anchor'];
			
			@$node->internaladdress = $matches['internal_address'];
			@$node->symlink = $matches['symlink'];
			@$node->internalselector = $matches['internal_selector'];
			
			@$node->externaladdress = $matches['external_address'];
			@$node->externalprotocol = $matches['external_proto'];
			@$node->externalselector = $matches['external_selector'];
		}
		if (($node->caption == '') and (empty($node->anchor))) $node->caption = $node->content;

		$node->prefix = "<a";
		$node->prefixtail = ">";
		$node->infix = $node->caption;
		$node->postfix = "</a>";
		$node = $this->prepare_link_node($node); 
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	#--------------------------------[ images ]------------------------------------#
	protected function image_emit($node) // i decorator "i"
	{
		$node->caption = $this->emit_children($node);
		// also available: $node->title
		$address = $node->content;
		$matches = array();
		if (preg_match($this->addr_re,$address,$matches))
		{
			@$node->internaladdress = $matches['internal_address'];
			@$node->symlink = $matches['symlink'];
			@$node->internalselector = $matches['internal_selector'];
			
			@$node->externaladdress = $matches['external_address'];
			@$node->externalprotocol = $matches['external_proto'];
			@$node->externalselector = $matches['external_selector'];
		}
		
		$node->prefix = "<img";
		$node->prefixtail = "/>";
		$node = $this->prepare_image_node($node); 
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail;
	}
	#--------------------------------[ lists ]-------------------------------------#
	protected function number_list_emit($node) // b decorator "ol"
	{
		$node->prefix = "\n<ol";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "\n</ol>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function bullet_list_emit($node) // b decorator "ul"
	{
		$node->prefix = "\n<ul";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "\n</ul>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function list_item_emit($node) // decorator "li"
	{
		$node->prefix = "\n<li";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "</li>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	#--------------------------------[ tables ]------------------------------------#
	protected function table_emit($node) // b decorator "table"
	{
		$node->prefix = "\n<table";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "\n</table>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function table_headrow_emit($node) // b decorator "tr"
	{
		$node->prefix = "\n<tr";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "\n</tr>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function table_row_emit($node) // b decorator "tr"
	{
		$node->prefix = "\n<tr";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "\n</tr>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function table_headcell_emit($node) // b decorator "th"
	{
		$node->prefix = "\n<th";
		$node->prefixtail = ">\n";
		$node->infix = $this->emit_children($node);
		$node->postfix = "</th>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function table_cell_emit($node) // b decorator "td"
	{
		$node->prefix = "\n<td";
		$node->prefixtail = ">\n";
		$node->infix = $this->emit_children($node);
		$node->postfix = "</td>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	#=========================[ special decorators ]===============================#
	#---------------------------[ span decoration ]--------------------------------#
	protected function span_emit($node) // i decorator "s"
	{
		$node->prefix = "<span";
		$node->prefixtail = ">";
		$node->infix = $this->emit_children($node);
		$node->postfix = "</span>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	#----------------------------[ block dividers ]--------------------------------#
	protected function blockdivider_emit($node) // b decorator "b"
	{
		$node->prefix = "\n<div";
		$node->prefixtail = ">";
		$node->infix = '';
		$node->postfix = "\n</div>";
		$node = $this->prepare_node($node);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	#============================[ preformatted text ]=============================#
	protected function code_emit($node) // i decorator "c"
	{
		$node->prefix = "<code";
		$node->prefixtail = ">";
		$node->infix = $node->content;
		$node->escapecontent = TRUE;
		$node->postfix = "</code>";
		$node = $this->prepare_node($node);
		if ($node->escapecontent) $node->infix = htmlspecialchars($node->infix);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	protected function preformatted_emit($node) // b decorator "pre"
	{
		$node->prefix = "\n<pre";
		$node->prefixtail = ">\n";
		$node->infix = $node->content;
		$node->escapecontent = TRUE;
		$node->postfix = "</pre>";
		$node = $this->prepare_node($node);
		if ($node->escapecontent) $node->infix = htmlspecialchars($node->infix);
		return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
			. $node->infix . $node->postfix;
	}
	#==============================[ advanced markup ]=============================#
	#------------------------------[ block declarations ]----------------------------#
	protected function blockdef_emit($node) // declaration decorator (various)
	{
		$blocktag = $node->blocktag;
		$knowntag = TRUE;
		if (!in_array($blocktag,$this->_blocktags)) 
		{
//			$blocktag = $node->blocktag = 'div'; // default
			$blocktag .= $node->blockid;
			$knowntag = FALSE;
			$node->prefix = "\n(:$blocktag " . $node->argumentstring;
			$node->prefixtail = ":)";
			$node->postfix = "\n(:{$blocktag}end:)";
		}
		else
		{
			$node->prefix = "\n<$blocktag";
			$node->prefixtail = ">";
			$node->postfix = "\n</$blocktag>";
		}
		$node->infix = $this->emit_children($node);
		if ($knowntag)
		{
			$node = $this->prepare_node($node);
			return $node->prefix . implode(' ',$node->attributes) . $node->prefixtail 
				. $node->infix . $node->postfix;
		}
		else
		{
			return $node->prefix . $node->prefixtail 
				. $node->infix . $node->postfix;
		}
	}
	#--------------------------------[ macros ]--------------------------------#
	protected function macro_emit($node) // macro decorator
	{
		$node->caption = $this->emit_children($node);
		$node = $this->prepare_macro($node);
		if ($node->processed)
		{
			return $node->output;
		}
		else
		{
			$prefix = '<<' . $node->macroname;
			$arguments = $node->argumentstring;
			if ($node->arguments != '') $node->arguments = ' ' . $node_arguments;
			if ($node->text != '') 
				$text = '|' . $node->text;
			else
				$text = '';
			$postfix = '>>';
			return htmlspecialchars($prefix . $arguments .  $text . $postfix);
		}
	}
}
