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
 * @package   framework
 * @author    Nicholas J. Kisseberth <nkissebe@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Hubzero\User;

/**
 * Helper class for users
 */
class Helper
{
	/**
	 * Generate a random password
	 *
	 * @param   integer  $length	Length of the random password
	 * @return  string
	 */
	public static function random_password($length = 8)
	{
		$genpass = '';
		$salt = "abchefghjkmnpqrstuvwxyz0123456789";

		srand((double)microtime()*1000000);

		$i = 0;

		while ($i < $length)
		{
			$num = rand() % 33;
			$tmp = substr($salt, $num, 1);
			$genpass = $genpass . $tmp;
			$i++;
		}

		return $genpass;
	}

	/**
	 * Encrypt a password
	 *
	 * @param   string  $password	Password to be encrypted
	 * @return  string
	 */
	public static function encrypt_password($password)
	{
		return "{MD5}" . base64_encode(pack('H*', md5($password)));
	}

	/**
	 * Get domain ID
	 *
	 * @param   string  $domain	Domain name
	 * @return  mixed
	 */
	public static function getXDomainId($domain)
	{
		$db = \App::get('db');

		if (empty($domain) || ($domain == 'hubzero'))
		{
			return false;
		}

		$query = 'SELECT domain_id FROM `#__xdomains` WHERE domain=' . $db->quote($domain) . ';';
		$db->setQuery($query);

		$result = $db->loadObject();

		if (empty($result))
		{
			return false;
		}

		return $result->domain_id;
	}

	/**
	 * Get domain user ID
	 *
	 * @param   string  $domain_username	Username of the domain
	 * @param   string  $domain				Domain name
	 * @return  mixed
	 */
	public static function getXDomainUserId($domain_username, $domain)
	{
		$db = \App::get('db');

		if (empty($domain) || ($domain == 'hubzero'))
		{
			return $domain_username;
		}

		$query = 'SELECT uidNumber FROM #__xdomain_users,#__xdomains WHERE ' .
				 '#__xdomains.domain_id=#__xdomain_users.domain_id AND ' .
				 '#__xdomains.domain=' . $db->quote($domain) . ' AND ' .
				 '#__xdomain_users.domain_username=' . $db->quote($domain_username);
		$db->setQuery($query);

		$result = $db->loadObject();

		if (empty($result))
		{
			return false;
		}

		return $result->uidNumber;
	}

	/**
	 * Delete a record by user ID
	 *
	 * @param   integer  $id	User ID
	 * @return  boolean
	 */
	public static function deleteXDomainUserId($id)
	{
		$db = \App::get('db');

		if (empty($id))
		{
			return false;
		}

		$id = intval($id);

		if ($id <= 0)
		{
			return false;
		}

		$query = 'DELETE FROM `#__xdomain_users` WHERE uidNumber=' . $db->quote($id) . ';';

		$db->setQuery($query);

		$db->query();

		return true;
	}

	/**
	 * Check if a user has a domain record
	 *
	 * @param   integer  $uid	User ID
	 * @return  boolean
	 */
	public static function isXDomainUser($uid)
	{
		$db = \App::get('db');

		$query = 'SELECT uidNumber FROM `#__xdomain_users` WHERE #__xdomain_users.uidNumber=' . $db->quote($uid);

		$db->setQuery($query);

		$result = $db->loadObject();

		if (empty($result))
		{
			return false;
		}

		return true;
	}

	/**
	 * Create a domain record if the domain does not exist in the db
	 *
	 * @param   string   $domain	Domain name
	 * @return  integer
	 */
	public static function createXDomain($domain)
	{
		$db = \App::get('db');

		if (empty($domain) || ($domain == 'hubzero'))
		{
			return false;
		}

		$query = 'SELECT domain_id FROM `#__xdomains` WHERE ' .
				 '#__xdomains.domain=' . $db->quote($domain);

		$db->setQuery($query);

		$result = $db->loadObject();

		if (empty($result))
		{
			$query = 'INSERT INTO `#__xdomains` (domain) VALUES (' . $db->quote($domain) . ')';

			$db->setQuery($query);

			$db->query();

			$domain_id = $db->insertid();
		}
		else
		{
			$domain_id = $result->domain_id;
		}

		return $domain_id;
	}

	/**
	 * Set a domain for a user
	 *
	 * @param   string   $domain_username	Username to be assigned the domain
	 * @param   string   $domain			Domain name
	 * @param   integer  $uidNumber			User id
	 * @return  bool
	 */
	public static function setXDomainUserId($domain_username, $domain, $uidNumber)
	{
		return self::mapXDomainUser($domain_username, $domain, $uidNumber);
	}

	/**
	 * Map a domain to a user
	 *
	 * @param   string   $domain_username	Username to be assigned the domain
	 * @param   string   $domain			Domain name
	 * @param   integer  $uidNumber			User id
	 * @return  boolean
	 */
	public static function mapXDomainUser($domain_username, $domain, $uidNumber)
	{
		$db = \App::get('db');

		if (empty($domain))
		{
			return 0;
		}

		$query = 'SELECT domain_id FROM `#__xdomains` WHERE ' .
				 '#__xdomains.domain=' . $db->quote($domain);

		$db->setQuery($query);

		$result = $db->loadObject();

		if (empty($result))
		{
			$query = 'INSERT INTO `#__xdomains` (domain) VALUES (' . $db->quote($domain) . ')';

			$db->setQuery($query);

			$db->query();

			$domain_id = $db->insertid();
		}
		else
		{
			$domain_id = $result->domain_id;
		}

		$query = 'INSERT INTO `#__xdomain_users` (domain_id,domain_username,uidNumber) ' .
			' VALUES (' . $db->quote($domain_id) . ',' .
			$db->quote($domain_username) . ',' . $db->quote($uidNumber) . ')';

		$db->setQuery($query);

		if (!$db->query())
		{
			return false;
		}

		return true;
	}

	/**
	 * Get a list of groups for a user
	 *
	 * @param   string  $uid	User ID
	 * @param   string  $type	all|applicants|members|managers|invitees
	 * @param   string  $cat	g.type
	 * @return  boolean
	 */
	public static function getGroups($uid, $type='all', $cat = null)
	{
		$db = \App::get('db');

		$g = '';
		if ($cat == 1)
		{
			$g .= "(g.type='".$cat."' OR g.type='3') AND";
		}
		elseif ($cat !== null)
		{
			$g .= "g.type=" . $db->quote($cat) . " AND ";
		}

		// Get all groups the user is a member of
		$query1 = "SELECT g.gidNumber, g.published, g.approved, g.cn, g.description, g.join_policy, '1' AS registered, '0' AS regconfirmed, '0' AS manager FROM #__xgroups AS g, #__xgroups_applicants AS m WHERE $g m.gidNumber=g.gidNumber AND m.uidNumber=".$uid;
		$query2 = "SELECT g.gidNumber, g.published, g.approved, g.cn, g.description, g.join_policy, '1' AS registered, '1' AS regconfirmed, '0' AS manager FROM #__xgroups AS g, #__xgroups_members AS m WHERE $g m.gidNumber=g.gidNumber AND m.uidNumber=".$uid;
		$query3 = "SELECT g.gidNumber, g.published, g.approved, g.cn, g.description, g.join_policy, '1' AS registered, '1' AS regconfirmed, '1' AS manager FROM #__xgroups AS g, #__xgroups_managers AS m WHERE $g m.gidNumber=g.gidNumber AND m.uidNumber=".$uid;
		$query4 = "SELECT g.gidNumber, g.published, g.approved, g.cn, g.description, g.join_policy, '0' AS registered, '1' AS regconfirmed, '0' AS manager FROM #__xgroups AS g, #__xgroups_invitees AS m WHERE $g m.gidNumber=g.gidNumber AND m.uidNumber=".$uid;

		switch ($type)
		{
			case 'all':
				$query = "( $query1 ) UNION ( $query2 ) UNION ( $query3 ) UNION ( $query4 )";
			break;
			case 'applicants':
				$query = $query1." ORDER BY description, cn";
			break;
			case 'members':
				$query = $query2." ORDER BY description, cn";
			break;
			case 'managers':
				$query = $query3." ORDER BY description, cn";
			break;
			case 'invitees':
				$query = $query4." ORDER BY description, cn";
			break;
		}

		$db->setQuery($query);

		$result = $db->loadObjectList();

		if (empty($result))
		{
			return false;
		}

		return $result;
	}

	/**
	 * Remove User From Groups
	 *
	 * @param   integer  $uid	User ID
	 * @return  boolean
	 */
	public static function removeUserFromGroups($uid)
	{
		$db = \App::get('db');
		$tables = array('#__xgroups_members', '#__xgroups_managers', '#__xgroups_invitees', '#__xgroups_applicants');

		foreach ($tables as $table)
		{
			$sql = "DELETE FROM `".$table."` WHERE uidNumber=" . $db->quote($uid);
			$db->setQuery($sql);
			$db->query();
		}

		return true;
	}

	/**
	 * Get all courses the user is a member of
	 *
	 * @param   string  $uid	User ID
	 * @param   string  $type	all|applicants|members|managers|invitees
	 * @param   string  $cat	g.type
	 * @return  boolean	If there is any course, return true. Otherwise, false.
	 */
	public static function getCourses($uid, $type='all', $cat = null)
	{
		$db = \App::get('db');

		$g = '';
		if ($cat == 1) {
			$g .= "(g.type='".$cat."' OR g.type='3') AND";
		}

		// Get all courses the user is a member of
		$query1 = "SELECT g.id, g.state, g.alias, g.title, g.join_policy, '1' AS registered, '0' AS regconfirmed, '0' AS manager FROM #__courses AS g, #__courses_applicants AS m WHERE $g m.course_id=g.id AND m.user_id=".$uid;
		$query2 = "SELECT g.id, g.state, g.alias, g.title, g.join_policy, '1' AS registered, '1' AS regconfirmed, '0' AS manager FROM #__courses AS g, #__courses_members AS m WHERE $g m.course_id=g.id AND m.user_id=".$uid;
		$query3 = "SELECT g.id, g.state, g.alias, g.title, g.join_policy, '1' AS registered, '1' AS regconfirmed, '1' AS manager FROM #__courses AS g, #__courses_managers AS m WHERE $g m.course_id=g.id AND m.user_id=".$uid;
		$query4 = "SELECT g.id, g.state, g.alias, g.title, g.join_policy, '0' AS registered, '1' AS regconfirmed, '0' AS manager FROM #__courses AS g, #__courses_invitees AS m WHERE $g m.course_id=g.id AND m.user_id=".$uid;

		switch ($type)
		{
			case 'all':
				$query = "( $query1 ) UNION ( $query2 ) UNION ( $query3 ) UNION ( $query4 )";
			break;
			case 'applicants':
				$query = $query1." ORDER BY title, alias";
			break;
			case 'members':
				$query = $query2." ORDER BY title, alias";
			break;
			case 'managers':
				$query = $query3." ORDER BY title, alias";
			break;
			case 'invitees':
				$query = $query4." ORDER BY title, alias";
			break;
		}

		$db->setQuery($query);

		$result = $db->loadObjectList();

		if (empty($result))
		{
			return false;
		}

		return $result;
	}

	/**
	 * Get common groups between two users
	 *
	 * @param   integer  $uid	One user ID
	 * @param   integer  $pid	The other user ID
	 * @return  array	Array containing all the interseted groups between two users
	 */
	public static function getCommonGroups($uid, $pid)
	{
		// Get the groups the visiting user
		$xgroups = self::getGroups($uid, 'all', 1);

		$usersgroups = array();
		if (!empty($xgroups))
		{
			foreach ($xgroups as $group)
			{
				if ($group->regconfirmed)
				{
					$usersgroups[] = $group->cn;
				}
			}
		}

		// Get the groups of the profile
		$pgroups = self::getGroups($pid, 'all', 1);

		// Get the groups the user has access to
		$profilesgroups = array();
		if (!empty($pgroups))
		{
			foreach ($pgroups as $group)
			{
				if ($group->regconfirmed)
				{
					$profilesgroups[] = $group->cn;
				}
			}
		}

		// Find the common groups
		return array_intersect($usersgroups, $profilesgroups);
	}
}
