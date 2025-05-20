FROM alpine:latest

RUN apk add --no-cache bash curl grep

WORKDIR /scripts

COPY docker/ngrok_setup/setup_ngrok.sh /scripts/setup_ngrok.sh
RUN chmod +x /scripts/setup_ngrok.sh

CMD ["/scripts/setup_ngrok.sh"]