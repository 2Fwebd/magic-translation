<?php

namespace Alkalab\MagicTranslate;

use Illuminate\Console\Command;
use Stichoza\GoogleTranslate\TranslateClient;
use Emoji;

class Translate extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'magic:translate {file} {target} {--no-validation}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Translate a specified localization file into a given target';

	/**
	 * The original string from the file, this is set directly in the loop to avoid arguments
	 * 
	 * @var string 
	 */
	protected $originalString = '';

	/**
	 * Translator client instance
	 *
	 * @var TranslateClient
	 */
	protected $translation = '';

	/**
	 * Supported HTML tags
	 *
	 * @var array
	 */
	protected $supportedTags = [
		'<i>',
		'</i>',
		'<b>',
		'</b>',
		'<span>',
		'</span>',
		'<div>',
		'</div>',
		'<p>',
		'</p>',
		'<strong>',
		'</strong>',
		'<stroke>',
		'</stroke>',
		'<blockquote>',
		'</blockquote>',
	];

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

		// We get our arguments
		$file_name = $this->argument('file');
		$target = $this->argument('target');

		// We get the file that needs to be translated
		$orginal_file_path = $this->getLocalPath($file_name);
		if (!file_exists($orginal_file_path)) {
			$this->error('File not found ❌');
			return;
		}

		// We initiate a translation client
		$this->translation = new TranslateClient();

		// We get the target file
		$orginal_array = include($orginal_file_path);
		$local_file_path = $this->getLocalPath($file_name, $target);
		$local_array = (file_exists($local_file_path)) ? include($local_file_path) : [];

		// We translate every string
		foreach ($orginal_array as $orginal_key => $orginal_translation) {

			$this->originalString = stripslashes($orginal_translation);

			// If it already exists
			if (isset($local_array[$orginal_key])) {
				$string = $local_array[$orginal_key];
			}

			// Otherwise we translate it
			else {

				// Emojis check
				$saved_emojis = $this->replaceInEmojis();

				// HTML tags check
				$saved_tags = $this->replaceInTags();

				// Variable check
				$saved_variables = $this->replaceInVariables();

				// We translate with Google Translate
				$string = $this->translation
					->setSource('en')
					->setTarget($target)
					->translate($this->originalString);


				// We put the Emojis back
				if ($saved_emojis) {
					foreach ($saved_emojis as $name => $val) {
						$string = str_replace($name, $val, $string);
					}
				}

				// We put the HTML tags back
				if ($saved_tags) {
					foreach ($saved_tags as $name => $val) {
						$string = str_replace($name, $val, $string);
					}
				}

				// We put the Variables back
				if ($saved_variables) {
					foreach ($saved_variables as $name => $val) {
						$string = str_replace($name, $val, $string);
					}
				}

				// We prompt for validation
				if (!$this->option('no-validation')) {

					// We ask the confirmation
					$this->info( 'ORIGINAL:' . $this->originalString );
					$correct = $this->confirm( 'TRANSLATED:' . $string );

					if (!$correct) {
						$string = $this->ask( 'Please type the translation 🚀' );
					}

				}

			}

			$local_array[$orginal_key] = addslashes($string);

		}

		file_put_contents($local_file_path, $this->formatToString($local_array));


		$this->line('All good 👌');

	}

	/**
	 * Detects all Emojis in a string and replace them with an unique key which is returned through an array
	 *
	 * @return array
	 */
	private function replaceInEmojis() {

		$emojis = Emoji\detect_emoji($this->originalString);

		$saved = [];

		if (!$emojis)
			return $saved;

		foreach ($emojis as $emoji_key => $emoji_array) {
			$this->originalString = str_replace($emoji_array['emoji'],'Emoji'.$emoji_key, $this->originalString);
			$saved['Emoji'.$emoji_key] = $emoji_array['emoji'];
		}

		return $saved;
	}

	/**
	 * Detects all HTML tags in a string and replaced with an unique key which is returned through an array
	 *
	 * @return array
	 */
	private function replaceInTags() {

		$saved = [];

		if ($this->originalString === strip_tags($this->originalString))
			return $saved;

		foreach ($this->supportedTags as $tag_key => $tag) {

			if (!str_contains($this->originalString, $tag))
				continue;

			$unique_key = 'Tag'. ucfirst($tag_key);

			$this->originalString = str_replace($tag, $unique_key, $this->originalString);

			$saved[$unique_key] = $tag;

		}

		return $saved;

	}

	/**
	 * Detects all localization variables in a string and replaced with an unique key which is returned through an array
	 *
	 * @return array
	 */
	private function replaceInVariables() {

		$saved = [];

		$this->originalString = preg_replace_callback('/:[a-z_]+/', function ($match) use (&$saved)  {

			static $id = 0;
			$id++;

			$saved['Variable'. $id] = $match;

			return 'Variable'. $id;

		}, $this->originalString);

		return $saved;

	}

	/**
	 * Turns a PHP array into a string and format it so it looks good
	 *
	 * @param $array
	 *
	 * @return string
	 */
	private function formatToString($array)
	{

		$string = '';
		$offset_base = 30;

		$string .= '<?php'. PHP_EOL;
		$string .= 'return ['. PHP_EOL;

		foreach ($array as $key=>$value) {

			$offset = $offset_base - strlen($key);
			$string .= str_repeat(' ', 4). '\''.$key.'\' '. str_repeat(' ', $offset) .' => \''.$value.'\','.PHP_EOL;

		}

		$string .= '];';

		return $string;

	}

	/**
	 * Returns a local file path
	 *
	 * @param string $name
	 * @param string $local
	 *
	 * @return string
	 */
	private function getLocalPath($name, $local = 'en')
	{
		return app()->langPath() .'/'. $local .'/'. $name . '.php';
	}

}
