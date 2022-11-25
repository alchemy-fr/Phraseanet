{{/*
Sets extra Phraseanet Ingress annotations
*/}}
{{- define "phraseanet.ingressAnnotations" -}}
  {{- if .Values.ingress.annotations }}
  annotations:
    {{- $tp := typeOf .Values.ingress.annotations }}
    {{- if eq $tp "string" }}
      {{- tpl .Values.ingress.annotations . | nindent 4 }}
    {{- else }}
      {{- toYaml .Values.ingress.annotations | nindent 4 }}
    {{- end }}
  {{- end }}
{{- end -}}



{{/*
Sets extra SAML Deployment annotations
*/}}
{{- define "saml.annotations" -}}
  {{- if .Values.saml.annotations }}
      annotations:
        {{- $tp := typeOf .Values.saml.annotations }}
        {{- if eq $tp "string" }}
          {{- tpl .Values.saml.annotations . | nindent 8 }}
        {{- else }}
          {{- toYaml .Values.saml.annotations | nindent 8 }}
        {{- end }}
  {{- end }}
{{- end -}}


{{/*
Creating Image Pull Secret
*/}}
{{- define "imagePullSecret" }}
{{- if .Values.image.pullSecret.enabled }}
{{- with .Values.image.pullSecret }}
{{- printf "{\"auths\":{\"%s\":{\"username\":\"%s\",\"password\":\"%s\",\"email\":\"%s\",\"auth\":\"%s\"}}}" .registry .username .password .email (printf "%s:%s" .username .password | b64enc) | b64enc }}
{{- end }}
{{- end }}
{{- end }}