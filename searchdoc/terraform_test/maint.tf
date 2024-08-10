provider "google" {
  project = var.project_id
  credentials = "creds.json"

}

resource "google_discovery_engine_data_store" "basic" {
  location                    = var.location
  data_store_id               = var.ds_id
  display_name                = var.ds_display_name
  industry_vertical           = "GENERIC"
  content_config              = "NO_CONTENT"
  solution_types              = ["SOLUTION_TYPE_SEARCH"]
  create_advanced_site_search = false
  project = var.project_id
}
resource "google_discovery_engine_search_engine" "basic2" {
  engine_id      = var.se_engine_id
  collection_id  = "default_collection"
  location       = google_discovery_engine_data_store.basic.location
  display_name   = var.se_datastore_display_name
  data_store_ids = [google_discovery_engine_data_store.basic.data_store_id]
  search_engine_config {
  }
  project = var.project_id
}

# Output block for data_store_id
output "data_store_id" {
  value = google_discovery_engine_data_store.basic.id
}

# Output block for search_engine_id
output "search_engine_id" {
  value = google_discovery_engine_search_engine.basic2.id
}

output "search_config_id" {
  value = google_discovery_engine_search_engine.basic2.search_engine_config
}