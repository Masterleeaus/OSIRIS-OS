{{- define "aiplatform.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "aiplatform.fullname" -}}
{{- printf "%s-%s" (include "aiplatform.name" .) .Release.Name | trunc 63 | trimSuffix "-" -}}
{{- end -}}
