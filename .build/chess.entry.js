/**
 * Build entry point for the browser bundle of chess.js.
 *
 * Bundled output: webroot/js/chess.bundle.js  (exposes `window.Chess`)
 *
 * Rebuild with:
 *   npx esbuild .build/chess.entry.js --bundle --format=iife --minify \
 *     --outfile=webroot/js/chess.bundle.js --legal-comments=none
 */
import { Chess } from 'chess.js';

window.Chess = Chess;
