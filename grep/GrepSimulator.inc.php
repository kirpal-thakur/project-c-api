<?php
/**
 * Class to find text inside other files.
 * @author Rochak Chauhan
 */
class GrepSimulator {

	public function __construct($toSearch,$rootPath,$seperator="/"){
		$dirInfo=$this->parseDirectory($rootPath,$seperator);
		$this->scanFileContents($toSearch,$dirInfo);
	}

	/**
	 * Function to retern all the directories, sub directors and files of the root directory
	 *
	 * @param string $rootPath
	 * @param string $seperator
	 * @return array
	 */
	private function parseDirectory($rootPath, $seperator="/"){
		$fileArray=array();
		if (($handle = opendir($rootPath))!==false) {
			while( ($file = readdir($handle))!==false) {
				if($file !='.' && $file !='..'){
					if (is_dir($rootPath.$seperator.$file)){
						$array=$this->parseDirectory($rootPath.$seperator.$file);
						$fileArray=array_merge($array,$fileArray);
						$fileArray[]=$rootPath.$seperator.$file;
					}
					else {
						$fileArray[]=$rootPath.$seperator.$file;
					}
				}
			}
		}
		return $fileArray;
	}

	/**
	 * Function to search file content for a string
	 *
	 * @param string $toSearch
	 * @param string $dirInfo
	 */
	private function scanFileContents($toSearch,$dirInfo) {
		if( ($count=count($dirInfo))>0 ) {
			for($i=0;$i<$count;$i++){
				if (is_file($dirInfo[$i])) {
					$file=str_replace("\\","/",$dirInfo[$i]);
					$fileContent=file_get_contents($file);
					$substrCount=substr_count($fileContent,$toSearch)."<hr />";
					if ($substrCount>0){
						echo "<hr /> $toSearch was found in $file <br />";
					}
				}
			}
		}
	}

}
?>
