<?php
class user_plugins extends rcube_plugin
{
	public $task='?(?!login|logout).*';
	const TABLE = 'user_plugins';
	private $db_exist = false;
	private $plugins_user = array();
	private $plugins_config = array();
	private $plugins_default = array();
	private $plugins_blacklist = array();
	private $rc = null;
	public function init()
	{
		$this->rc = rcube::get_instance();
		$this->load_config();
		$this->plugins_blacklist = $this->rc->config->get('user_plugins_blacklist', array());
		$username = $this->rc->user->data['username'];
		$sql = 'SELECT name, value FROM ' . $this->rc->db->table_name(self::TABLE) . ' WHERE user_id = ?';
		$qRec = $this->rc->db->query($sql, $this->rc->user->data['user_id']);
		while ($r = $this->rc->db->fetch_assoc($qRec))
		{
			$this->db_exist = true;
			if (substr($r['value'], 0, 1)=='{' || substr($r['value'], 0, 1)=='[')
			{
				$r['value'] = json_decode($r['value'], true);
			}
			switch($r['name'])
			{
				case 'plugins':
					$this->plugins_user = $r['value'];
					break;
				default:
					// $rcmail->config->set($r['name'], $r['value']);
			}
		}
		if(!$this->db_exist)
			$this->plugins_user = $this->rc->config->get('user_plugins_default', array());

		if (is_array($this->plugins_user))
		{
			$this->plugins_config = $this->rc->config->get('plugins', array());
			foreach($this->plugins_user as $plugin)
			{
				if (!in_array($plugin, $this->plugins_config))
					$this->rc->plugins->load_plugin($plugin, true);
			}
		}

		$this->add_texts('localization/');
		if($this->rc->task == 'settings')
		{
			$this->include_stylesheet($this->local_skin_path().'/css/settings.css');
			$this->add_hook('preferences_sections_list', array($this, 'preferences_sections_list'));
			$this->add_hook('preferences_list', array($this, 'preferences_list'));
			$this->add_hook('preferences_save', array($this, 'preferences_save'));
		}
	}
	function getAllPlugins(){
		$Plugins = array();
		$Handle = @opendir(__DIR__.'/../');
		while($Item = @readdir($Handle)){
			if($Item=='.'||$Item=='..') continue;
			if(is_dir(__DIR__.'/../'.$Item)){
				if(!in_array($Item, $this->plugins_config) &&
				   !in_array($Item, $this->plugins_blacklist) &&
				   substr($Item, 0, 3) != 'lib')
					$Plugins[] = $Item;
			}
		}
		sort($Plugins);
		return $Plugins;
	}
	function preferences_sections_list($p)
	{
		$p['list']['user_plugins'] = array(
			'id' => 'user_plugins',
			'section' => $this->gettext('user_plugins'),
		);
		return $p;
	}
	function preferences_list($p)
	{
		if($p['section'] != 'user_plugins') return $p;
		$Items = $this->getAllPlugins();
		$PluginsOpt = array(); $PluginsOptV = array();
		$PluginsBlk = array();
		foreach($Items as $Item){
			if(is_array($this->plugins_user) && in_array($Item, $this->plugins_user)) $Input = 'on';
			else $Input = '';
			$PluginsOptV[$Item] = rcube_utils::get_input_value('user_plugins_'.$Item, rcube_utils::INPUT_POST);
			$PluginsOpt[$Item] = new html_inputfield(array(
				'name' => 'user_plugins_'.$Item,
				'type' => 'checkbox', 'checked' => $PluginsOptV[$Item] != '' ? $PluginsOptV[$Item] : $Input
			));
			$PluginsBlk[$Item] = array('title'=> $Item, 'content' => $PluginsOpt[$Item]->show());
		}
		$p['blocks']['user_plugins_preferences_section'] = array(
			'options' => $PluginsBlk,
			'name' => rcube::Q($this->gettext('user_plugins_settings'))
		);
		return $p;
	}
	function preferences_save($p)
	{
		$Items = $this->getAllPlugins();
		$PluginsOptV = array();
		$PluginsBlk = array();
		foreach($Items as $Item){
			$Value = rcube_utils::get_input_value('user_plugins_'.$Item, rcube_utils::INPUT_POST);
			if($Value=='on'){
				$PluginsOptV[] = $Item;
			}
		}
		$JSON = json_encode($PluginsOptV);
		if($this->db_exist){
			$sql = "UPDATE ".$this->rc->db->table_name('user_plugins', true)." SET value = ? WHERE ".$this->rc->db->table_name('user_plugins', true).".id = ?;";
			$qRec = $this->rc->db->query($sql, $JSON, $this->rc->user->data['user_id']);
		}else{
			$sql = "INSERT INTO ".$this->rc->db->table_name('user_plugins', true)." (user_id, name, value) VALUES (?, 'plugins', ?);";
			$qRec = $this->rc->db->query($sql, $this->rc->user->data['user_id'], $JSON);
		}
		return $p;
	}
}
