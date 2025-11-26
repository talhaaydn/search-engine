.PHONY: build up composer-install import-content setup clean rebuild generate-secret

build:
	docker compose build --no-cache

up:
	docker compose up -d

generate-secret:
	@SECRET=$$(openssl rand -hex 32); \
	if [ -f .env ]; then \
		sed -i.bak "s/^APP_SECRET=.*/APP_SECRET=$$SECRET/" .env && rm .env.bak; \
		echo "APP_SECRET güncellendi: $$SECRET"; \
	else \
		echo "APP_SECRET=$$SECRET" >> .env; \
		echo "APP_SECRET oluşturuldu: $$SECRET"; \
	fi

composer-install:
	docker compose exec php composer install

import-content:
	docker compose exec php php bin/console app:import-content

setup: build up generate-secret composer-install import-content
	@echo "Kurulum tamamlandı! Uygulama http://localhost:8080 adresinde çalışıyor."

clean:
	docker compose down -v --rmi all

rebuild: clean setup