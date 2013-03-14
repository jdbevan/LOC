<?php

$directory = './';
$exclude = array();

$arguments = $argv;
while(null !== ($argument = array_shift($arguments))) {

	switch ($argument) {
		case "--directory":
			$directory = array_shift($arguments);
			break;
		case "--exclude-dir":
			$exclude[] = array_shift($arguments);
			break;
	}
}

class LinesOfCode {
	private $exclude;
	private $folders = 0;
	private $files = 0;
	private $filesize = 0;
	private $comments = 0;
	private $multiComments = 0;
	private $whitespace = 0;
	private $tags = 0;
	private $code = 0;
	private $functions = 0;
	private $queries = 0;
	
	public function __construct($directory, $exclude = null) {
		$this->exclude = (is_array($exclude)) ? $exclude : array();
		$this->loc($directory);

		$total = $this->tags + $this->whitespace + $this->comments + $this->multiComments + $this->code;
		
		if ($total==0) die("No stats gathered!");

		echo "PHP Files:\t\t".$this->files." (".round($this->filesize/1024/1024,2)."MB)\n";
		echo "Folders:\t\t".$this->folders."\n";
		echo "PHP Tags:\t\t".$this->tags."\n";
		echo "Blank lines:\t\t".$this->whitespace." (".round($this->whitespace/$total*100,2)."%)\n";
		echo "Single comment lines:\t".$this->comments." (".round($this->comments/$total*100,2)."%)\n";
		echo "Multi line comments:\t".$this->multiComments." (".round($this->multiComments/$total*100,2)."%)\n";
		echo "Functions:\t\t".$this->functions."\n";
		echo "Queries:\t\t".$this->queries."\n";
		echo "LOC:\t\t\t".$this->code." (".round($this->code/$total*100,2)."%)\n";
	}
	private function loc($directory) {
		if (in_array($directory, $this->exclude)) {
			return;
		}
		if ($handle = @opendir($directory)) {
			
			while (false !== ($entry = readdir($handle))) {
				
				if ($entry != "." and $entry != "..") {
					
					$file = $directory . DIRECTORY_SEPARATOR . $entry;
					if (is_dir($file)) {
						$this->folders++;
						$this->loc($file);
					} else if (preg_match("/\.php$/", $entry)) {
						
						$this->files++;
						$this->filesize += filesize($file);
						$fh = fopen($file, 'r');
						if ($fh) {
							while (false !== ($line = @fgets($fh, 4096))) {
								
								// Blank line
								if (preg_match("/^\s*$/", $line)) {
									$this->whitespace++;
								}
								// Open tags
								else if (preg_match("/^\s*<\?php\s*$/", $line) || preg_match("/^\s*\?>\s*$/", $line)) {
									/*
									* Massive comment
									* on several lines
									* for the win
									*/
									$this->tags++;
								}
								// Single comments
								else if (preg_match("/^\s*\/\//", $line)) {
									$this->comments++;
								}
								// Multiline comments
								else if (preg_match("/\/\*/", $line)) {
									
									if (preg_match("/^\s*\/\*/", $line)) {
										// If whitespace is only other thing before comment
										$this->multiComments++;
									} else {
										$this->countCode($line); //de++;
									}
									
									if (!preg_match("/\/\*.*\*\//", $line)) {
										// If not multiline comment on one line
										while (false !== ($line = @fgets($fh, 4096))) {
											$this->multiComments++;
											if (preg_match("/\*\//", $line)) {
												break;
											}
										}
									}
									
								}
								else if (!preg_match("/^\s*[{}]\s*$/", $line)) {
									$this->countCode($line);
								}
							}
							if (!feof($fh)) {
								echo "fgets() failed... for $file\n";
							}
							fclose($fh);
						}
					}
				}
			}
		} else {
			echo "Can't open directory: $directory\n";
			return false;
		}
	}
	private function countCode($line) {
		$this->code++;
		if (preg_match("/\bfunction\b/", $line)) {
			$this->functions++;
		}
		if (preg_match("/mysqli?_query\(/", $line)) {
			$this->queries++;
		}
	}
}

new LinesOfCode($directory, $exclude);

?>
