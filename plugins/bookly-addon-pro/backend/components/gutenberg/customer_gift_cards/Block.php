<?php
namespace BooklyPro\Backend\Components\Gutenberg\CustomerGiftCards;

use Bookly\Lib as BooklyLib;

class Block extends BooklyLib\Base\Block
{
    /**
     * @inheritDoc
     */
    public static function registerBlockType()
    {
        self::enqueueScripts( array(
            'module' => array(
                'js/gift-cards-block.js' => array( 'wp-blocks', 'wp-components', 'wp-element', 'wp-editor' ),
            ),
        ) );

        wp_localize_script( 'bookly-gift-cards-block.js', 'BooklyGiftCardsL10n', array(
            'block' => array(
                'title' => 'Bookly - ' . __( 'Gift cards', 'bookly' ),
                'description' => __( 'A custom block for displaying gift cards', 'bookly' ),
            ),
        ) );

        register_block_type( 'bookly/gift-cards-block', array(
            'editor_script' => 'bookly-gift-cards-block.js',
        ) );
    }
}
