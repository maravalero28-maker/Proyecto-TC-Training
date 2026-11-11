<?php
$tiposMensaje = $tiposMensaje ?? unserialize(TIPOS_MENSAJE);
?>
<article class="msg-editor">
    <fieldset class="form-group">
        <label for="msg-plantilla" class="form-label">Tipo de mensaje</label>
        <select id="msg-plantilla" class="form-control"></select>
        <section class="msg-acciones">
            <button type="button" id="btn-predeterminar" class="btn btn-outline btn-sm">Hacer predeterminado</button>
            <button type="button" id="btn-eliminar-tipo" class="btn btn-outline btn-sm text-danger">Eliminar tipo</button>
        </section>
    </fieldset>

    <fieldset class="form-group">
        <label for="msg-texto" class="form-label">Mensaje (puedes editarlo antes de enviar)</label>
        <textarea id="msg-texto" class="form-control" rows="6" maxlength="1000"></textarea>
        <p class="msg-ayuda">Variables disponibles: {nombre} {apellido} {plan} {dias}. <span id="msg-contador"></span></p>
    </fieldset>

    <fieldset class="form-group">
        <span class="form-label">Vista previa</span>
        <output id="msg-preview" class="msg-preview"></output>
    </fieldset>

    <fieldset class="form-group msg-canal">
        <span class="form-label">Enviar por</span>
        <label><input type="radio" name="msg-canal" value="whatsapp" checked> WhatsApp</label>
        <label><input type="radio" name="msg-canal" value="sms"> SMS (solo desde un celular)</label>
    </fieldset>

    <fieldset class="msg-guardar">
        <legend class="form-label">Guardar este texto como un nuevo tipo de mensaje</legend>
        <section class="msg-guardar-fila">
            <input type="text" id="msg-titulo" class="form-control" maxlength="100" placeholder="Título (ej. Recordatorio de pago)">
            <select id="msg-tipo" class="form-control">
                <?php foreach ($tiposMensaje as $clave => $etiqueta): ?>
                    <option value="<?php echo htmlspecialchars($clave); ?>"><?php echo htmlspecialchars($etiqueta); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="btn-guardar-tipo" class="btn btn-outline btn-sm">Guardar tipo</button>
        </section>
    </fieldset>

    <output id="msg-estado" class="msg-estado" aria-live="polite"></output>
</article>
