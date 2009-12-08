<?php 
class PhpBURN_Migrations {
	public function migrate(PhpBURN_Core $model = null) {
		if($model == null) {
			$packages = PhpBURN_Configuration::getConfig();
			foreach($packages as $package => $packageConfig) {
				self::loadModels($packageConfig);
			}
		} else {
			$model->getDialect()->migrate();
		}
	}
	
	public function unloadModels(PhpBURN_ConfigurationItem $packageConfig ) {
		
	}
	
	public function loadModels(PhpBURN_ConfigurationItem $packageConfig) {
//		Determine the wildcard based ONLY in SYS_MODEL_EXT to not load extra garbage	
		$dir = sprintf("%s*%s",$packageConfig->class_path . DS . $packageConfig->package . DS ,SYS_MODEL_EXT);
		
//		Searching
		foreach(glob( $dir ) as $filename) {
//			Fixing some stuff in the name
			$filename = explode(DS,$filename);
			$filename = end($filename);
			$filename = explode(SYS_MODEL_EXT, $filename);
			array_pop($filename);
			$filename = implode('',$filename);
			
//			Loading model
			if( !is_dir($filename) ) {
				PhpBURN::import($packageConfig->package . '.' . $filename );
				$model = new $filename;
				
				if($model instanceof PhpBURN_Core) {
					if($_SERVER['HTTP_HOST'])
						print "<pre>";
						
					print sprintf("Creating %s table for %s model from %s package into %s database: ", $model->_tablename, get_class($model), $packageConfig->package, $packageConfig->database);
					if( $model->getDialect()->migrate($model) ) {
						print "OK \r\n";
					} else {
						print "FAIL \r\n";
					}				
				}
				unset($model);
				if($_SERVER['HTTP_HOST'])
						print "</pre>";
			}
		}
	}
}
?>