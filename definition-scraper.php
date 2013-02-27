<?php

/**
 * Dictionary Scraper
 *
 * Requires the object $this->text, with each paragraph stored as a child ($this->text->0,
 * $this->text->1, etc.) Creates an object, named $this->definitions, that contains a list of all
 * identified terms and their definitions.
 * 
 * PHP version 5
 *
 * @author		Waldo Jaquith <waldo at jaquith.org>
 * @copyright	2013 Waldo Jaquith
 * @license		http://www.gnu.org/licenses/gpl.html GPL 3
 * @version		1.0
 * @link		http://www.statedecoded.com/
 * @since		1.0
 *
 */

class DefinitionScraper
{
	
	function parse()
	{

		if (!isset($this->text))
		{
			return false;
		}
		
		/*
		 * Create a list of every phrase that can be used to link a term to its defintion, e.g.,
		 * "'People' has the same meaning as 'persons.'" When appropriate, pad these terms with
		 * spaces, to avoid erroneously matching fragments of other terms.
		 */
		$linking_phrases = array(	' mean ',
									' means ',
									' shall include ',
									' includes ',
									' has the same meaning as ',
									' shall be construed ',
									' shall also be construed to mean ',
								);
		
		/* Measure whether there are more straight quotes or directional quotes in this passage
		 * of text, to determine which type are used in these definitions. We double the count of
		 * directional quotes since we're only counting one of the two directions.
		 */
		if ( substr_count($this->text, '"') > (substr_count($this->text, '”') * 2) )
		{
			$quote_type = 'straight';
			$quote_sample = '"';
		}
		else
		{
			$quote_type = 'directional';
			$quote_sample = '”';
		}
		
		/*
		 * Create the empty array that we'll build up with the definitions found in this section.
		 */
		$definitions = array();
		
		/*
		 * Step through each paragraph and determine which contain definitions.
		 */
		foreach ($this->text as &$paragraph)
		{
			
			/*
			 * Strip out any HTML.
			 */
			$paragraph = strip_tags($paragraph);
			
			/*
			 * Defined terms are wrapped in quotation marks, so use those as a criteria to round
			 * down our candidate paragraphs.
			 */
			if (strpos($paragraph, $quote_sample) !== false)
			{
				
				/*
				 * Iterate through every linking phrase and see if it's present in this paragraph.
				 * We need to find the right one that will allow us to connect a term to its
				 * definition.
				 */
				foreach ($linking_phrases as $linking_phrase)
				{
				
					if (strpos($paragraph, $linking_phrase) !== false)
					{
					
						/*
						 * Extract every word in quotation marks in this paragraph as a term that's
						 * being defined here. Most definitions will have just one term being
						 * defined, but some will have two or more.
						 */
						preg_match_all('/("|“)([A-Za-z]{1})([A-Za-z,\'\s-]*)([A-Za-z]{1})("|”)/', $paragraph, $terms);
						
						/*
						 * If we've made any matches.
						 */
						if ( ($terms !== false) && (count($terms) > 0) )
						{
							
							/*
							 * We only need the first element in this multi-dimensional array, which
							 * has the actual matched term. It includes the quotation marks in which
							 * the term is enclosed, so we strip those out.
							 */
							if ($quote_type == 'straight')
							{
								$terms = str_replace('"', '', $terms[0]);
							}
							elseif ($quote_type == 'directional')
							{
								$terms = str_replace('“', '', $terms[0]);
								$terms = str_replace('”', '', $terms);
							}
							
							/*
							 * Eliminate whitespace.
							 */
							$terms = array_map('trim', $terms);
							
							/* Lowercase most (but not necessarily all) terms. Any term that
							 * contains any lowercase characters will be made entirely lowercase.
							 * But any term that is in all caps is surely an acronym, and should be
							 * stored in its original case so that we don't end up with overzealous
							 * matches. For example, a two-letter acronym like "CA" is a valid
							 * definition, and we don't want to match every time "ca" appears within
							 * a word. (Though note that we only match terms surrounded by word
							 * boundaries.)
							 */
							foreach ($terms as &$term)
							{
								/*
								 * Drop noise words that occur in lists of words.
								 */
								if (($term == 'and') || ($term == 'or'))
								{
									unset($term);
									continue;
								}
							
								/*
								 * Step through each character in this word.
								 */
								for ($i=0; $i<strlen($term); $i++)
								{
									/*
									 * If there are any lowercase characters, then make the whole
									 * thing lowercase.
									 */
									if ( (ord($term{$i}) >= 97) && (ord($term{$i}) <= 122) )
									{
										$term = strtolower($term);
										break;
									}
								}
							}
							
							/*
							 * This is absolutely necessary. Without it, the following foreach()
							 * loop will simply use $term as-is through each loop, rather than
							 * spawning new instances based on $terms. This is presumably a bug in
							 * the current version of PHP (5.2), because it surely doesn't make any
							 * sense.
							 */
							unset($term);
							
							/*
							 * Step through all of our matches and save them as discrete
							 * definitions.
							 */
							foreach ($terms as $term)
							{
								
								/*
								 * It's possible for a definition to be preceded by other text, or
								 * to appear within a series of definitions on the same line.
								 * Solution: Start definitions at the first quotation mark.
								 */
								if ($quote_type == 'straight')
								{
									$paragraph = substr($paragraph, strpos($paragraph, '"'));
								}
								elseif ($quote_type == 'directional')
								{
									$paragraph = substr($paragraph, strpos($paragraph, '“'));
								}
								
								/*
								 * Comma-separated lists of multiple words being defined need to
								 * have the trailing commas removed.
								 */
								if (substr($term, -1) == ',')
								{
									$term = substr($term, 0, -1);
								}
								
								/*
								 * If we don't yet have a record of this term.
								 */
								if (!isset($definitions[$term]))
								{
									/*
									 * Append this definition to our list of definitions.
									 */
									$definitions[$term] = $paragraph;
								}
								
								/* If we already have a record of this term. This is for when a word
								 * is defined twice, once to indicate what it means, and one to list
								 * explicitly what it doesn't mean.
								 */
								else
								{
									/*
									 * Make sure that they're not identical -- this can happen if
									 * the defined term is repeated, in quotation marks, in the body
									 * of the definition.
									 */
									if ( trim($definitions[$term]) != trim($paragraph) )
									{
										/*
										 * Append this definition to our list of definitions.
										 */
										$definitions[$term] .= ' '.$paragraph;
									}
								}
							} // end iterating through matches
						} // end dealing with matches
						
						/*
						 * Because we have identified the linking phrase for this paragraph, we no
						 * longer need to continue to iterate through linking phrases.
						 */
						break;
						
					} // end matched linking phrase
				} // end iterating through linking phrases
			} // end this candidate paragraph
			
			/*
			 * We don't want to accidentally use this the next time we loop through.
			 */
			unset($terms);
		}
		
		/*
		 * We may well find absolutely no definitions.
		 */
		if (count($definitions) == 0)
		{
			return false;
		}
		
		/*
		 * Make the list of definitions a subset of a larger variable, so that we can store things
		 * other than terms.
		 */
		$tmp = array();
		$tmp['terms'] = $definitions;
			
		/*
		 * Save our list of definitions, converted from an array to an object.
		 */
		$this->definitions = (object) $tmp;
		
		return true;
	}
}
