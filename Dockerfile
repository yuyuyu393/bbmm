FROM nginx:stable-alpine

# 加ARG参数强制让COPY层失效，每次构建都会重新复制配置文件
ARG BUILD_TS
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 8080
CMD ["nginx", "-g", "daemon off;"]
# fix sub_filter replace jian.jiandin.com domain v2
