<?php
// Load Config File
require('configs.php');

/**
 * RSSifier
 * RSS-ify a website without a valid XML RSS Feed
 *
 * @author Oliver Raduner <github@raduner.ch>
 * @date 07-08-2013
 * @version 1.0
 * @copyright Public
 *
 * @todo parseDOMtable is not generic
 * @todo parseDOMelement is not flexible enough
 *
 * @package RSSifier
 */
class RSSify
{
	/**
	 * HTML Form Input
	 * Generic Form to input required Metadata
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @todo if $prefillValue 'DOMtype' is given, pre-select the correct dropdown entry
	 *
	 * @return string HTML formatted code
	 */
	public function GenericInputForm($prefillValues = NULL)
	{
		$html = sprintf('
			<h3>
				%1$s
			</h3>
			<form method="POST" id="xml_metadata">
				<fieldset>
					 <legend>Metadata</legend>
					 <label>Full Source URL</label><input type="text" name="sourceURL" placeholder="http://..." class="input-xxlarge" value="%2$s" />
					 <label>Base URL for single items</label><input type="text" name="baseURL" placeholder="http://..." class="input-xxlarge" value="%3$s" />
					 <div><label>Elements are contained inside</label>
					 <select class="form-control" name="DOMtype">
					  <option>table</option>
					  <option>h1</option>
					  <option>h2</option>
					  <option>h3</option>
					  <option>div</option>
					  <option>span</option>
					  <option>p</option>
					 </select>
					 <label>Element selector</label> <input type="text" name="elementSelector" placeholder=[@class="className"] class="input-large" value="%5$s" />
					 <label>Occurrence # of container on the site</label> <input type="text" name="elementOccurrence" placeholder="0 | 1 | ..." class="input-mini" value="%4$s" />
					 <br />
					 <button type="submit" name="preview" class="btn btn-info btn-large">Validate/Preview</button> <button type="submit" name="generate" class="btn btn-primary btn-large">Generate Feed</button>
				</fieldset>
			</form>',
			'RSS XML-Feed Config',
			$prefillValues['sourceURL'],
			$prefillValues['baseURL'],
			$prefillValues['elementOccurrence'],
			$prefillValues['elementSelector']);

		return $html;
	}


	/**
	 * Form Validator
	 * Validate Form inputs sent via $_POST
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	public function ValidateFormVars($array, $ignoreList)
	{
		if (count($array) > 0)
		{
			foreach ($array as $key => $value) {
				if (is_null($value) && !in_array($key, $ignoreList)) CustomErrors::addError("Found empty <i>'$key'</i> in submitted Form!");
			}
		} else {
			CustomErrors::addError("ALL fields are <i>empty</i> in submitted Form!");
		}
	}


	/**
	 * URL-Builder
	 * Build URL for direct Feed access
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	public function OutputFeedURL($base, $array)
	{
		if (count($array) > 0)
		{
			$feedURL = $base.'?';
			$i = 0;
			foreach ($array as $key => $value)
			{
				$feedURL .= $key.'='.$value;
				$i++;
				if ($i != count($array)) $feedURL .= '&';
			}
			return $feedURL;
		} else {
			return false;
		}
	}


	/**
	 * RSS XML-Builder
	 * RSS 2.0 compliant XML-Feed output
	 * @link http://feed2.w3.org/docs/rss2.html
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @return string
	 */
	public function RSS($url, $baseURL, $elementsOfType='', $elementOccurrence=0, $elementSelector='')
	{
		if (!empty($url))
		{
			// PHP 5 Native DOM Parser
			$html = new DOMDocument();
			if (@$html->loadHTMLFile($url))
			{
				$feedPublisher = $this->getDOMelements($html, 'title');
				if (!is_null($feedPublisher))
				{
					$feedMetadata = $this->getDOMelements($html, 'meta');
					foreach ($feedMetadata as $feedMeta)
					{
						foreach ($feedMeta as $metakey => $metaItem)
						{
							if($metakey == 'description')
								$feedDescription = $metaItem;
							if($metakey == 'keywords')
								$feedKeywords = $metaItem;
						}
					}

					$feed = sprintf('<?xml version="1.0" encoding="utf-8"?>
					<rss version="2.0"
						xmlns:dc="http://purl.org/dc/elements/1.1/"
						xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
						xmlns:admin="http://webns.net/mvcb/"
						xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
						xmlns:content="http://purl.org/rss/1.0/modules/content/">

						<channel>
							<title>%1$s</title>
							<link>%2$s</link>
							<description>%3$s</description>
							<category>%4$s</category>
							<dc:creator>%1$s</dc:creator>
							<dc:date>%5$s</dc:date>
							<admin:generatorAgent rdf:resource="%6$s" />
							<admin:errorReportsTo rdf:resource="mailto:%7$s"/>
							<sy:updatePeriod>%8$s</sy:updatePeriod>
							<sy:updateFrequency>%9$s</sy:updateFrequency>
							<sy:updateBase>%10$s</sy:updateBase>
							',
						$feedPublisher[0],
						$url,
						$feedDescription,
						$feedKeywords,
						date('D, d M Y H:i:s', time()),
						AppName,
						AppAuthor,
						'hourly',
						'1',
						'2010-01-01T12:00+00:00');

					$feedItems = $this->getDOMelements($html, $elementsOfType, $elementOccurrence, $elementSelector);
					if (count($feedItems) > 0)
					{
						foreach ($feedItems as $i=>$xmlitem)
						{
							$xmlitem_title = htmlentities($xmlitem['title']);
							$xmlitem_link = $baseURL.$xmlitem['url'];
							$xmlitem_pubDate = $xmlitem['pubDate'];
							$xmlitem_author = $feedPublisher[0];
							$xmlitem_guid = $xmlitem_link;
							$xmlitem_description = '<![CDATA[';
							$xmlitem_description .= $xmlitem_title;
							$xmlitem_description .= ']]>';
							$xmlitem_content = '<a title="'.$xmlitem_title.'" href="'.$xmlitem_link.'">'.$xmlitem_title.'</a>';

							// XML Feed items schreiben
							$feed .= sprintf('
								<item>
									<title>%1$s</title>
									<link>%2$s</link>
									<pubDate>%3$s</pubDate>
									<author>%4$s</author>
									<guid isPermaLink="false">%5$s</guid>
									<description>%6$s</description>
									<content:encoded><![CDATA[%7$s]]></content:encoded>
								</item>',
								$xmlitem_title,
								$xmlitem_link,
								$xmlitem_pubDate,
								$xmlitem_author,
								$xmlitem_guid,
								$xmlitem_description,
								$xmlitem_content);

						} // end foreach

					} else { // if 0 results
						CustomErrors::addError('No items found!', pathinfo(__FILE__, PATHINFO_FILENAME), __LINE__);
					} // end if count()

					// Closing tags for the XML
					$feed .= '
						</channel>
					</rss>';

					// Let's show the beauty!
					echo $feed;

				} else {
					CustomErrors::addError('No Page Title found', pathinfo(__FILE__, PATHINFO_FILENAME), __LINE__);
				}

			} else {
				CustomErrors::addError('Cannot parse DOM for given URL', pathinfo(__FILE__, PATHINFO_FILENAME), __LINE__);
			}
		} else {
			CustomErrors::addError('No URL given...', pathinfo(__FILE__, PATHINFO_FILENAME), __LINE__);
		}
	}


	/**
	 * Parse and Echo Site Elements
	 * @link: http://forums.phpfreaks.com/topic/213295-breaking-down-a-table/
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @return array
	 */
	private function getDOMelements($DOM, $elementOfType, $occurence=0, $selector='')
	{
		$DOMitems = array();

		switch ($elementOfType):
		case 'table':
			$DOMitems = $this->parseDOMtable($DOM, $occurence);
		break;

	default:
		$DOMitems = $this->parseDOMelement($DOM, $elementOfType, $selector);

		endswitch;

		if (!is_null($DOMitems)) {
			return $DOMitems;
		} else {
			CustomErrors::addError('No elements to parse...', pathinfo(__FILE__, PATHINFO_FILENAME), __LINE__);
		}
	}


	/**
	 * Parse DOM elements by HTML-Table-Tag
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @return array
	 */
	private function parseDOMtable($DOM, $occurence=0)
	{
		$tables = array();
		$rows = array();
		$col = array();
		$DOMitems =  array();

		$tables = $DOM->getElementsByTagName('table');
		$rows = $tables->item($occurence)->getElementsByTagName('tr');

		if (!is_null($rows))
		{
			$i = 0;
			foreach ($rows as $row)
			{
				$col = $row->getElementsByTagName('td');
				if (!empty($col->item(0)->nodeValue)) {
					//$DOMitems[$i]['pubDate'] = date('D, d M Y H:i:s', strtotime($col->item(0)->nodeValue));
					$DOMitems[$i]['pubDate'] = date('r', strtotime($col->item(0)->nodeValue));
					$DOMitems[$i]['title'] = htmlentities($col->item(1)->nodeValue, ENT_NOQUOTES, 'UTF-8');
					$DOMitems[$i]['url'] = htmlentities($col->item(1)->getElementsByTagName('a')->item(0)->getAttribute('href'));
					$i++;
				}
			}
			return $DOMitems;
		}
	}


	/**
	 * Parse DOM elements by HTML-Tag
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @return array
	 */
	private function parseDOMelement($DOM, $tagName, $occurence=0, $scope='')
	{
		$elements = array();
		$DOMitems =  array();

		$elements = $DOM->getElementsByTagName($tagName.$scope);

		if (!is_null($elements))
		{
			if ($tagName != 'meta') {
				// Regular code for most HTML elements (except Meta Data, see "else")
				foreach ($elements as $element)
				{
					array_push($DOMitems, htmlentities($element->nodeValue, ENT_NOQUOTES, 'UTF-8'));
				}
			} else {
				// Special Code for parsing Site Meta Data
				for ($i = 0; $i < $elements->length; $i++)
				{
					$meta = $elements->item($i);
					$DOMitems[] = array(strtolower($meta->getAttribute('name')) => htmlentities($meta->getAttribute('content'), ENT_NOQUOTES, 'UTF-8'));
				}
			}
			return $DOMitems;
		}
	}

}


/**
 * Little Error Helper
 * Handle and output Errors - if any
 *
 * @author Oliver Raduner <github@raduner.ch>
 * @date 04-08-2013
 * @version 1.0
 *
 * @package RSSifier
 * @subpackage ErrorHandler
 */
class CustomErrors
{
	/**
	 * Error-Adding
	 * Add an Error to the Errors Array
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @global array Custom Error Catcher
	 */
	public function addError($string, $file='', $line = '?')
	{
		global $errors;
		array_push($errors, "<strong>$string</strong><br /><i>--- $file @ Line $line</i></p>");
	}

	/**
	 * Error-Counter
	 * Counts all gathered Errors in the Errors Array
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @global array Custom Error Catcher
	 * @return boolean
	 */
	public function areThereAnyErrors()
	{
		global $errors;
		return (count($errors) > 0) ? TRUE : FALSE;
	}

	/**
	 * Error-Output
	 * Returns an Array containing all Errors in case there are any
	 *
	 * @version 1.0
	 * @since 1.0
	 *
	 * @global array Custom Error Catcher
	 * @return array
	 */
	public function outputErrors()
	{
		global $errors;

		if (!is_null($errors))
		{
			return $errors;
		} else {
			return 0;
		}
	}
}

// Initialize the RSSify Class
$RSSify = new RSSify();

// Initialize the Error Handler Class
$errors = array();
$ErrorHandler = new CustomErrors();