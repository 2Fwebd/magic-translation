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

			$orginal_translation = stripslashes($orginal_translation);

			// If it already exists
			if (isset($local_array[$orginal_key])) {
				$string = $local_array[$orginal_key];
			}

			// Otherwise we translate it
			else {

				// Emoji check
				if($emojis = Emoji\detect_emoji($orginal_translation)) {
					$saved_emojis = [];
					foreach ($emojis as $emoji_key => $emoji_array) {
						$orginal_translation = str_replace($emoji_array['emoji'],'Emoji'.$emoji_key, $orginal_translation);
						$saved_emojis['Emoji'.$emoji_key] = $emoji_array['emoji'];
					}
				}

				// We translate with Google Translate
				$string = $this->translation
					->setSource('en')
					->setTarget($target)
					->translate($orginal_translation);


				// We put the Emoji back
				if (isset($saved_emojis)) {
					foreach ($saved_emojis as $emoji_name => $emoji_val) {
						$string = str_replace($emoji_name, $emoji_val, $string);
					}
				}

				// We prompt for validation
				if (!$this->option('no-validation')) {

					// We ask the confirmation
					$this->info( 'ORIGINAL:' . $orginal_translation );
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
