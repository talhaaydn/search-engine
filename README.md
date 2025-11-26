# Search Engine Service

## Gereksinimler

- Docker
- Docker Compose
- Make (opsiyonel)

## Kurulum

### Yöntem 1: Make ile (Önerilen)

#### 1. Projeyi Klonlayın

```bash
git clone <repository-url>
cd search-engine-service
```

#### 2. .env Dosyasını Oluşturun

```bash
cp .env.example .env
```

#### 3. Projeyi Başlatın

```bash
make setup
```

Bu komut otomatik olarak:
- Docker imajlarını oluşturur
- Tüm servisleri başlatır
- APP_SECRET oluşturur
- Composer bağımlılıklarını yükler
- İçerikleri import eder

#### 5. Uygulamaya Erişin

- **Web Uygulaması**: http://localhost:8080
- **API Dokümanı**: http://localhost:8080/api/docs

---

### Yöntem 2: Make Olmadan Manuel Kurulum

#### 1. Projeyi Klonlayın

```bash
git clone <repository-url>
cd search-engine-service
```

#### 2. .env Dosyasını Oluşturun

```bash
cp .env.example .env
```

#### 3. APP_SECRET Oluşturun

```bash
SECRET=$(openssl rand -hex 32)
sed -i.bak "s/^APP_SECRET=.*/APP_SECRET=$SECRET/" .env && rm .env.bak
echo "APP_SECRET güncellendi: $SECRET"
```

#### 4. Docker İmajlarını Oluşturun

```bash
docker compose build --no-cache
```

Bu işlem 5-15 dakika sürebilir (ilk seferde).

#### 5. Servisleri Başlatın

```bash
docker compose up -d
```

Container'lar başladığında `docker-entrypoint.sh` otomatik olarak:
- Veritabanını bekler
- Migration'ları çalıştırır
- Cache'i temizler

#### 6. Composer Bağımlılıklarını Yükleyin

```bash
docker compose exec php composer install
```

#### 7. İçerikleri Import Edin

```bash
docker compose exec php php bin/console app:import-content
```

#### 8. Uygulamaya Erişin

- **Web Uygulaması**: http://localhost:8080
- **RabbitMQ Management**: http://localhost:15672 (kullanıcı: guest, şifre: guest)

---

## Teknolojiler

- **PHP**: 8.2
- **Symfony**: 7.2
- **PostgreSQL**: 16
- **Elasticsearch**: 8.11
- **Redis**: 7
- **RabbitMQ**: 3.13
- **Nginx**: Alpine
- **Docker & Docker Compose**

## Mimari Kararlar

### Neden PostgreSQL?

İlişkisel veri saklama ihtiyacı için MySQL ve PostgreSQL arasında değerlendirme yapıldı. Bu proje kapsamında MySQL'in sunduğu özellikler yeterli olsa da, projenin ölçeklenebilirliği ve gelecekteki büyüme potansiyeli göz önünde bulundurularak PostgreSQL tercih edildi.

### Neden Elasticsearch?

PostgreSQL güçlü bir veritabanı olmasına rağmen, büyük ölçekli projelerde full-text search işlemlerinde gerekli performansı sağlamakta yetersiz kalabilir. Bu nedenle, arama işlemlerini optimize etmek amacıyla Elasticsearch entegre edildi.

Elasticsearch, özellikle metin tabanlı aramalarda PostgreSQL'e göre çok daha yüksek performans sunar. PostgreSQL'deki her içerik değişikliği, event-driven mimari kullanılarak otomatik olarak Elasticsearch'e senkronize edilir ve böylece her iki sistemin avantajlarından yararlanılır.

