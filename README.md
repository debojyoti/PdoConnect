# How to use in your project

## Include in your composer.json

    "repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/debojyoti/PdoConnect",
            "autoload": {
                "psr-4" : {
                    "Debojyoti\\PdoConnect" : "src"
                }
            }
        }
    ],
      "require": {
        "Debojyoti/PdoConnect": "dev-master"
    }

# Usage

    use Debojyoti\PdoConnect\Handler;

    $con = new Handler();