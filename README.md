# OptOutAcceptAndMail
LimeSurvey Plugin that creates a new OptOut page at /index.php/plugins/direct/plugin/OptOutAcceptAndMail with customisable text, and the ability to email the participant / administrator when opting out

The structure of the URL is:

/index.php/plugins/direct/plugin/OptOutAcceptAndMail/surveyId/SURVEYHERE/token/TOKENHERE/

So when referring to it in an email invitation use the following string:

NEWOPTOUTURL

and the plugin will replace it.

## Installation

Download the zip from the [releases](https://github.com/adamzammit/OptOutAcceptAndMail/releases) page and extract to your plugins folder. You can also clone directly from git: go to your plugins directory and type 
```
git clone https://github.com/adamzammit/OptOutAcceptAndMail.git OptOutAcceptAndMail
```
## Security

If you discover any security related issues, please email adam@acspri.org.au instead of using the issue tracker.

## Contributing

PR's are welcome, but if it is for a new feature/big change please create an issue first to check if it will be accepted.

## Usage

You are free to use/change/fork this code for your own products, and I would be happy to hear how and what you are using it for!
