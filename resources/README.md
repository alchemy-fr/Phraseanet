## Dev workflow for the ./resources directory

### For js and css code
 
 - Make your modification
 - Increment `assetFileVersion` in both files before commit
     - `lib/Alchemy/Phrasea/Twig/PhraseanetExtension.php`
     -  `Phraseanet-production-client/config/config.js`
 - Copy assets in www/assets folder ```make install_asset```
