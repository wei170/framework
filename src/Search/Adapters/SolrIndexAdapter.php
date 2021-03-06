<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Hubzero\Search\Adapters;

use Hubzero\Search\IndexInterface;
use Solarium;

/**
 * SolrIndexAdapter - Index adapter for Solr using the Solarium library
 * 
 * @uses IndexInterface
 * @uses Solarium 
 */
class SolrIndexAdapter implements IndexInterface
{
	/**
	 * __construct - Constructor for adapter, sets config and established connection
	 * 
	 * @param mixed $config - Configuration object
	 * @access public
	 * @return void
	 */
	public function __construct($config)
	{
		// Some setup information
		$core = $config->get('solr_core');
		$port = $config->get('solr_port');
		$host = $config->get('solr_host');
		$path = $config->get('solr_path');

		$this->logPath = $config->get('solr_log_path');

		// Build the Solr config object
		$solrConfig = array( 'endpoint' =>
			array( $core  =>
				array('host' => $host,
							'port' => $port,
							'path' => $path,
							'core' => $core,
							)
						)
					);

		// Create the client
		$this->connection = new Solarium\Client($solrConfig);

		// Create the Solr Query object
		$this->query = $this->connection->createSelect();
	}

	/**
	 * getLogs - Returns an array of search engine query log entries
	 * 
	 * @access public
	 * @return void
	 */
	public function getLogs()
	{
		if (file_exists($this->logPath))
		{
			$log = Filesystem::read($this->logPath);
			$levels = array();
			$this->logs = explode("\n", $log);
		}
		else
		{
			return array();
		}

		return $this->logs;
	}

	/**
	 * lastInsert - Returns the timestamp of the last document indexed
	 * 
	 * @access public
	 * @return void
	 */
	public function lastInsert()
	{
		$query = $this->connection->createSelect();
		$query->setQuery('*:*');
		$query->setFields(array('timestamp'));
		$query->addSort('timestamp', 'DESC');
		$query->setRows(1);
		$query->setStart(0);

		$results = $this->connection->execute($query);
		foreach ($results as $document)
		{
			foreach ($document as $field => $value)
			{
				$result = $value;
				return $result;
			}
		}
	}

	/**
	 * status - Checks whether or not the search engine is responding 
	 *
	 * @access public
	 * @return void
	 */
	public function status()
	{
		try
		{
			$pingRequest = $this->connection->createPing();
			$ping = $this->connection->ping($pingRequest);
			$pong = $ping->getData();
			$alive = false;

			if (isset($pong['status']) && $pong['status'] === "OK")
			{
				return true;
			}
		}
		catch (\Solarium\Exception $e)
		{
			return false;
		}
	}


	/**
	 * index - Stores a document within an index
	 * 
	 * @param mixed $document 
	 * @access public
	 * @return void
	 */
	public function index($document)
	{
		// Instantiate an update object
		$update = $this->connection->createUpdate();

		// Create the document for updating
		$solrDoc = $update->createDocument();

		// Iterate through and set the appropriate fields
		foreach ($document as $key => $value)
		{
			$solrDoc->$key = $value;
		}

		if (!isset($solrDoc->id))
		{
			// Generate a unique ID, hopefully
			$solrDoc->id = hash('md5', time()*rand());
		}

		// Add the document to the update
		$update->addDocuments(array($solrDoc));

		// Create a commit
		$update->addCommit();

		// Run the update query
		if ($this->connection->update($update))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * deleteById - Removes a single document from the search index
	 *
	 * @param string $id
	 * @access public
	 * @return boolean 
	 */
	public function delete($id)
	{
		if ($id != null)
		{
			$update = $this->connection->createUpdate();
			$update->addDeleteQuery('id:'.$id);
			$update->addCommit();
			$response = $this->connection->update($update);

			// @FIXME: Increase error checking 
			// Wild assumption that the update was successful
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * updateIndex - Updates a document existing in the search index
	 *
	 * @param mixed $document
	 * @param mixed $id
	 * @access public
	 * @return void
	 */
	public function updateIndex($document, $id)
	{
			$update = $this->connection->createUpdate();

			$solrDoc = $update->createDocument();

			foreach ($document as $key => $value)
			{
				$solrDoc->$key = $value;
			}

			$solrDoc->id = $id;

			$update->addDocuments(array($solrDoc));
			$update->addCommit();
			$this->connection->update($update);

			return true;
	}

	/**
	 * parseQuery - Translates symbols from query string
	 *
	 * @param  array $query - Passes in the query array
	 * @access private
	 * @return void
	 */
	private function parseQuery($query)
	{
		switch ($query['operator'])
		{
			case '=':
				$string = $query['field'] . ':' . $query['value'];
			break;
		}
		return $string;
	}
}
