var compressorOverride = MODx.panel.Resource;

Ext.override(compressorOverride, {

    compressorOriginals: {
        getSettingRightFieldset:  compressorOverride.prototype.getSettingRightFieldset,
        getSettingRightFieldsetRight: compressorOverride.prototype.getSettingRightFieldsetRight,
    },

    getSettingRightFieldset: function (config) {

        originals = this.compressorOriginals.getSettingRightFieldset.call(this, config);
        if (!config.record.contentType || config.record.contentType === 'text/html') {
            originals.push({
                xtype: 'xcheckbox',
                boxLabel: _('resource_compressed'),
                description: '<b>[[*compressed]]</b><br />' + _('resource_compressed_help'),
                hideLabel: true,
                name: 'compressed',
                id: 'modx-resource-compressed',
                inputValue: 1,
                checked: parseInt(config.record.compressed) || 1,
                convertValue: function (v) {
                    return (
                        v === '1' || v === true || v === 'true' ||
                        v === this.submitOnValue || String(v).toLowerCase() === 'on'
                    );
                }
            });
        }

        return originals;
    },

});
