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
	private $files = 0;
	private $comments = 0;
	private $multiComments = 0;
	private $whitespace = 0;
	private $tags = 0;
	private $code = 0;
	private $functions = 0;
	
	public function __construct($directory, $exclude = null) {
		$this->loc($directory);
		$total = $this->tags + $this->whitespace + $this->comments + $this->multiComments + $this->code;
		echo "Files: ".$this->files."\n";
		echo "PHP Tags: ".$this->tags." (".round($this->tags/$total*100,2)."%)\n";
		echo "Whitespace: ".$this->whitespace." (".round($this->whitespace/$total*100,2)."%)\n";
		echo "Comments: ".$this->comments." (".round($this->comments/$total*100,2)."%)\n";
		echo "Multi line comments: ".$this->multiComments." (".round($this->multiComments/$total*100,2)."%)\n";
		echo "Functions: ".$this->functions."\n";
		echo "LOC: ".$this->code." (".round($this->code/$total*100,2)."%)\n";
	}
	private function loc($directory) {
		if ($handle = opendir($directory)) {
			
			while (false !== ($entry = readdir($handle))) {
				
				if ($entry != "." and $entry != "..") {
					
					if (is_dir($directory . "/" . $entry)) {
						$this->loc($directory . "/" . $entry); //, $files, $comments, $whitespace, $code);
					} else if (preg_match("/\.php$/", $entry)) {
						
						$this->files++;
						$fh = fopen($directory . "/" . $entry, 'r');
						if ($fh) {
							while (false !== ($line = fgets($fh, 4096))) {
								
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
										$this->code++;
									}
									
									if (!preg_match("/\/\*.*\*\//", $line)) {
										// If not multiline comment on one line
										while (false !== ($line = fgets($fh, 4096))) {
											$this->multiComments++;
											if (preg_match("/\*\//", $line)) {
												break;
											}
										}
									}
									
								}
								else if (!preg_match("/^\s*[{}]\s*$/", $line)) {
									$this->code++;
									if (preg_match("/\bfunction\b/", $line)) {
										$this->functions++;
									}
								}
							}
							if (!feof($fh)) {
								echo "fgets() failed...";
							}
							fclose($fh);
						}
					}
				}
			}
		} else {
			echo "Can't open directory: $directory\n";
		}
	}
}

new LinesOfCode($directory);		

?>