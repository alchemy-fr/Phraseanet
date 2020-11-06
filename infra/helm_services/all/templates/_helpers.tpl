{{- define "ps.fullname" -}}
{{- if .Values.fullnameOverride -}}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- $name := default "ps" .Values.nameOverride -}}
{{- if contains $name .Release.Name -}}
{{- .Release.Name | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}
{{- end -}}

{{- define "ps.name" -}}
{{- .Values.nameOverride | default "ps" | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "volumes.configs" }}
- name: configs
  configMap:
    name: {{ .Values.globalConfig.externalConfigmapName | default (printf "%s-configs" .Release.Name) }}
{{- end }}

{{- define "secretRef.adminOAuthClient" }}
- secretRef:
    name: {{ .Values.params.adminOAuthClient.externalSecretName | default (printf "%s-admin-oauth-client-secret" .Release.Name) }}
{{- end }}

{{- define "secretName.rabbitmq" -}}
{{- .Values.rabbitmq.externalSecretName | default "rabbitmq-secret" -}}
{{- end }}
{{- define "secretName.postgresql" -}}
{{- .Values.postgresql.externalSecretName | default "postgresql-secret" -}}
{{- end }}

{{- define "secretRef.ingress.tls.wildcard" -}}
{{- with .Values.ingress.tls.wildcard }}
{{- if and .enabled .externalSecretName -}}
{{- .externalSecretName -}}
{{- else -}}
gateway-tls
{{- end }}
{{- end }}
{{- end }}

{{- define "envFrom.rabbitmq" }}
- configMapRef:
    name: rabbitmq-php-config
- secretRef:
    name: {{ include "secretName.rabbitmq" . }}
{{- end }}

{{- define "envFrom.postgresql" }}
- configMapRef:
    name: postgresql-php-config
- secretRef:
    name: {{ include "secretName.postgresql" . }}
{{- end }}

{{- define "secretRef.postgresql" }}
- secretRef:
    name: {{ .Values.postgresql.externalSecretName | default "api-db-secret" }}
{{- end }}

{{- define "configMapRef.phpApp" -}}
{{- $appName := .app }}
{{- $ctx := .ctx }}
{{- $glob := .glob }}
- configMapRef:
    name: php-config
- configMapRef:
    name: urls-config
{{- end }}

{{- define "envRef.phpApp" -}}
{{- $appName := .app }}
{{- $ctx := .ctx }}
{{- $glob := .glob }}
{{- if or (eq $appName "uploader") (eq $appName "expose") }}
{{- $secretName := $ctx.api.config.s3Storage.externalSecretKey | default (printf "%s-s3-secret" $appName) }}
{{- $mapping := $ctx.api.config.s3Storage.externalSecretMapping }}
- name: S3_STORAGE_ACCESS_KEY
  valueFrom:
    secretKeyRef:
      name: {{ $secretName }}
      key: {{ $mapping.accessKey }}
- name: S3_STORAGE_SECRET_KEY
  valueFrom:
    secretKeyRef:
      name: {{ $secretName }}
      key: {{ $mapping.secretKey }}
{{- end }}
{{- end }}

{{- define "app.volumes" }}
{{- $appName := .app -}}
{{- $ctx := .ctx -}}
{{- $glob := .glob -}}
{{- if .glob.Values._internal.volumes }}
{{- if hasKey $glob.Values._internal.volumes $appName }}
{{- with (index $glob.Values._internal.volumes $appName) }}
{{- range $key, $value := . }}
- name: {{ $key }}
{{- if $ctx.persistence.enabled }}
  persistentVolumeClaim:
    claimName: {{ $ctx.persistence.existingClaim | default (printf "%s-%s" $value.name (include "ps.fullname" $glob)) }}
{{- else }}
  emptyDir: {}
{{- end }}
{{- end }}
{{- end }}
{{- end }}
{{- end }}
{{- end }}

{{- define "app.volumesMounts" }}
{{- $appName := .app -}}
{{- $ctx := .ctx -}}
{{- $glob := .glob -}}
{{- if .glob.Values._internal.volumes }}
{{- if hasKey .glob.Values._internal.volumes $appName }}
{{- with (index .glob.Values._internal.volumes $appName) }}
{{- range $key, $value := . }}
- name: {{ $key }}
  mountPath: {{ $value.mountPath }}
{{- end }}
{{- end }}
{{- end }}
{{- end }}
{{- end }}

{{- define "app.volumesUidInit" }}
{{- $appName := .app -}}
{{- $ctx := .ctx -}}
{{- $glob := .glob -}}
{{- if hasKey .glob.Values._internal.volumes $appName }}
{{- with (index .glob.Values._internal.volumes $appName) }}
{{- range $key, $value := . }}
{{- if $value.uid }}
initContainers:
- name: volume-set-uid-{{ $appName }}-{{ $key }}
  image: busybox
  command: ["sh", "-c", "chown -R {{ $value.uid }}:{{ $value.uid }} {{ $value.mountPath }}"]
  volumeMounts:
  - name: {{ $key }}
    mountPath: {{ $value.mountPath }}
{{- end }}
{{- end }}
{{- end }}
{{- end }}
{{- end }}

{{- define "app.s3Storage.configMap" }}
{{- $ctx := .ctx -}}
{{- $glob := .glob -}}
S3_STORAGE_ENDPOINT: {{ $ctx.s3Storage.endpoint | default (ternary "http://minio:9000" "" $glob.Values.minio.enabled) | quote }}
S3_STORAGE_BASE_URL: {{ tpl $ctx.s3Storage.baseUrl $glob | quote }}
S3_STORAGE_REGION: {{ $ctx.s3Storage.region | default "eu-central-1" | quote }}
S3_STORAGE_USE_PATH_STYLE_ENDPOINT: {{ ternary "\"true\"" "\"false\"" (or $ctx.s3Storage.usePathSyleEndpoint $glob.Values.minio.enabled) }}
S3_STORAGE_BUCKET_NAME: {{ $ctx.s3Storage.bucketName | quote }}
{{- end }}
