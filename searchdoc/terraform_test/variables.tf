variable "project_id" {
  default = "your_project_id"
  sensitive = true
  description = "your_project_id"


}

variable "location" {

type = string
description = "get from session"
default = "global"
  
}



variable "ds_display_name" {

  type = string
  description = "get from session"
  default = "name ds"
  
}
variable "se_datastore_display_name" {

  type = string
  description = "get from session"
  default = "name se"
}

variable "se_engine_id" {

  type = string
  description = "get from tf output"
  # sensitive = true
  default = "id-se-8"

}

variable "ds_id" {

  type = string
  description = "get from tf output"
  # sensitive = true
  default = "id-ds-8"
  
  
}

