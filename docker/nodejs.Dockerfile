FROM node:22-alpine

WORKDIR /var/www

ADD . /var/www

# RUN npm install

CMD tail -f /dev/null
