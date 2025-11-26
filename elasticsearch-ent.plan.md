# Elasticsearch Entegrasyonu MVP

## 1. Elasticsearch Infrastructure Setup

**Docker Compose Konfigürasyonu**

- [`compose.yaml`](compose.yaml) dosyasına Elasticsearch ve Kibana servisleri eklenecek
- Elasticsearch 8.x single-node cluster (development için)
- Kibana web UI (opsiyonel, monitoring için)
- Healthcheck ve volume konfigürasyonu
- Network: mevcut `search_engine_network` kullanılacak

**Environment Variables**

- `.env` dosyasına Elasticsearch connection bilgileri eklenecek
- `ELASTICSEARCH_HOST`, `ELASTICSEARCH_PORT` parametreleri

## 2. Composer Dependencies

**Ruflin Elasticsearch PHP Client**

```bash
composer require ruflin/elastica
```

- Elasticsearch PHP client library
- Symfony entegrasyonu kolay
- Doctrine ile uyumlu

## 3. Elasticsearch Service Layer

**Yeni Dosyalar:**

- `src/Service/Elasticsearch/ElasticsearchService.php`: Ana Elasticsearch service
- `src/Service/Elasticsearch/ContentIndexer.php`: Content indexleme logic
- `config/packages/elastica.yaml`: Elasticsearch konfigürasyonu

**ElasticsearchService Sorumlulukları:**

- Elasticsearch client yönetimi
- Index oluşturma ve mapping tanımlama
- Bulk indexing operasyonları
- Health check

**ContentIndexer Sorumlulukları:**

- Content entity'sini Elasticsearch document'e dönüştürme
- Single ve bulk indexing
- Document güncelleme ve silme

**Index Mapping:**

```json
{
  "properties": {
    "id": {"type": "integer"},
    "title": {"type": "text", "analyzer": "standard"},
    "contentType": {"type": "keyword"},
    "score": {"type": "float"},
    "createdAt": {"type": "date"}
  }
}
```

## 4. Doctrine Event Listeners

**Yeni Dosya:**

- `src/EventListener/ContentElasticsearchSyncListener.php`

**Dinlenecek Olaylar:**

- `postPersist`: Yeni content oluşturulduğunda ES'e index et
- `postUpdate`: Content güncellendiğinde ES'de güncelle
- `preRemove`: Content silindiğinde ES'den sil

**Önemli:**

- [`src/Entity/Content.php`](src/Entity/Content.php) zaten `#[ORM\HasLifecycleCallbacks]` kullanıyor
- Event listener `services.yaml`'da tag ile register edilecek

## 5. Repository Değişiklikleri

**Yeni Dosya:**

- `src/Repository/ElasticsearchContentRepository.php`

**Sorumluluklar:**

- Elasticsearch query builder
- [`ContentSearchRequestDTO`](src/DTO/Content/ContentSearchRequestDTO.php) parametrelerini ES query'sine çevirme
- Keyword search (match query)
- ContentType filtering (term query)
- Score sorting
- Pagination

**Query Örneği:**

```php
// keyword: "symfony", contentType: "article", sortByScore: "desc"
{
  "query": {
    "bool": {
      "must": [
        {"match": {"title": "symfony"}}
      ],
      "filter": [
        {"term": {"contentType": "article"}}
      ]
    }
  },
  "sort": [{"score": "desc"}],
  "from": 0,
  "size": 20
}
```

## 6. ContentService Refactoring

**Değişiklik:** [`src/Service/Content/ContentService.php`](src/Service/Content/ContentService.php)

**Değişiklikler:**

- Constructor'a `ElasticsearchContentRepository` inject edilecek
- `search()` metodu artık Elasticsearch kullanacak
- Mevcut `ContentRepository` kaldırılacak (veya fallback için tutulabilir)
- Response mapping aynı kalacak (DTO'lar değişmeyecek)

**Öncesi:**

```php
$this->contentRepository->searchContents($request)
```

**Sonrası:**

```php
$this->elasticsearchContentRepository->search($request)
```

## 7. Console Command - Initial Indexing

**Yeni Dosya:**

- `src/Command/IndexContentToElasticsearchCommand.php`

**Amaç:**

- Mevcut database'deki tüm content'leri Elasticsearch'e index etme
- Batch processing (1000'er kayıt)
- Progress bar gösterimi
- İlk kurulumda ve re-indexing gerektiğinde kullanılacak

**Kullanım:**

```bash
php bin/console app:index-content-elasticsearch
```

## 8. Service Configuration

**Değişiklik:** [`config/services.yaml`](config/services.yaml)

**Eklenecekler:**

- Elasticsearch client bean tanımı
- ElasticsearchService configuration
- Event listener tag registration
- Environment variable binding

## 9. Testing & Verification

**Manuel Test Adımları:**

1. `docker compose up -d` ile Elasticsearch başlatma
2. `php bin/console app:index-content-elasticsearch` ile initial indexing
3. `/api/contents?keyword=test` endpoint'ini test etme
4. Kibana Dev Tools ile index'i kontrol etme
5. Yeni content ekleme ve otomatik indexing'i doğrulama

**Doğrulama:**

- Elasticsearch'te index oluşturuldu mu?
- Search sonuçları doğru mu?
- Response time iyileşti mi?
- CRUD operasyonlarında sync çalışıyor mu?

## Önemli Dosyalar

Ana değişiklikler:

- [`compose.yaml`](compose.yaml) - Elasticsearch container
- [`src/Service/Content/ContentService.php`](src/Service/Content/ContentService.php) - ES entegrasyonu
- [`src/Entity/Content.php`](src/Entity/Content.php) - Event listener için
- [`config/services.yaml`](config/services.yaml) - Service registration

Yeni dosyalar:

- `src/Service/Elasticsearch/ElasticsearchService.php`
- `src/Service/Elasticsearch/ContentIndexer.php`
- `src/Repository/ElasticsearchContentRepository.php`
- `src/EventListener/ContentElasticsearchSyncListener.php`
- `src/Command/IndexContentToElasticsearchCommand.php`
- `config/packages/elastica.yaml`

## Beklenen Performans İyileştirmesi

- Search query süresi: ~500ms → ~50ms (10x daha hızlı)
- Keyword search: LIKE query yerine full-text search
- Relevance scoring: Elasticsearch BM25 algoritması
- Scalability: Milyonlarca kayıt için hazır