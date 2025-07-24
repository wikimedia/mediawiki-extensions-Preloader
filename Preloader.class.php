<?php

use MediaWiki\Hook\EditFormPreloadTextHook;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;

class Preloader implements EditFormPreloadTextHook {

	private RevisionLookup $revisionLookup;

	public function __construct( RevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function onEditFormPreloadText( &$text, $title ): bool {
		$src = self::preloadSource( $title->getNamespace() );
		if ( $src ) {
			$stx = self::sourceText( $src );
			if ( $stx ) {
				$text = $stx;
			}
		}
		return true;
	}

	/**
	 * Determine what page should be used as the source of preloaded text
	 * for a given namespace and return the title (in text form)
	 *
	 * @param int $namespace Namespace to check for
	 * @return string|bool Name of the page to be preloaded or bool false
	 */
	private function preloadSource( int $namespace ): bool|string {
		global $wgPreloaderSource;
		return $wgPreloaderSource[$namespace] ?? false;
	}

	/**
	 * Grab the current text of a given page if it exists
	 *
	 * @param string $page Text form of the page title
	 * @return string|bool
	 */
	private function sourceText( string $page ): bool|string {
		$title = Title::newFromText( $page );
		if ( $title && $title->exists() ) {
			$revisionRecord = $this->revisionLookup->getRevisionByTitle( $title );

			$content = $revisionRecord->getContent( SlotRecord::MAIN );
			$text = $content instanceof TextContent ? $content->getText() : '';
			return self::transform( $text );
		} else {
			return false;
		}
	}

	/**
	 * Remove sections from the text and trim whitespace
	 *
	 * @param string $text
	 * @return string
	 */
	private function transform( string $text ): string {
		$text = trim( preg_replace( '/<\/?includeonly>/s', '', $text ) );
		return trim( preg_replace( '/<noinclude>.*<\/noinclude>/s', '', $text ) );
	}
}
