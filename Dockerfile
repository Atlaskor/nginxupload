# Use a lean Nginx image
FROM nginx:alpine

# Remove the default Nginx configuration
RUN rm /etc/nginx/conf.d/default.conf

# Copy our custom Nginx configurations
COPY ./nginx.conf /etc/nginx/nginx.conf
COPY ./default.conf /etc/nginx/conf.d/default.conf

# Create the web root directory and give Nginx user ownership
RUN mkdir -p /var/www/html
RUN chown -R nginx:nginx /var/www/html

# Expose port 80
EXPOSE 80

# The default Nginx entrypoint will run Nginx
CMD ["nginx", "-g", "daemon off;"]
