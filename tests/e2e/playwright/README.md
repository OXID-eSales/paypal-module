# Playwright E2E Testing
## How to Install
1. Navigate to the folder. `tests/e2e/playwright`
2. 
``` bash
   cp .env.dist .env
    # Edit .env to set your environment variables
   make install
```
### Run tests in Headless Mode
``` bash
   make test
```
### View Tests in Realtime (Headed Mode)
Run tests with a visible browser:
``` bash
   make play
```

## How to Run and View Tests on a URL
To test and see a hosted environment, ensure the in is set to: `BASE_URL``.env`
``` dotenv
BASE_URL=https://<oxid-shop-hostname>
```

Navigate to the **Playwright Web Interface** (for trace, logs):
``` bash
http://127.0.0.1:4040
```



## How to run with ngrok
To run tests with ngrok, you need to set up ngrok to tunnel your local server. Follow these steps:
1. Install ngrok if you haven't already.
``` bash
    curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null && \
    echo "deb https://ngrok-agent.s3.amazonaws.com buster main" | sudo tee /etc/apt/sources.list.d/ngrok.list && \
    sudo apt-get update && sudo apt-get install ngrok
``` 
2. Run ngrok with your custom domain:
``` bash
      ngrok config add-authtoken <get_your_own_token_from_ngrok>
      ngrok http --hostname=<get_your_own_domain_from_ngrok> 80 > /dev/null 2>&1 &
```

### Test your ngrok connection
``` bash
          curl -I https://<get_your_own_domain_from_ngrok>
          curl -v https://<get_your_own_domain_from_ngrok>
          curl -o /dev/null -s -w "%{http_code}\n" https://<get_your_own_domain_from_ngrok>
```         

### Update config.inc.php
Add ngrok domain to your `Oxid's config.inc.php` file.
