<?php if ((!$isRoot && $conditionList) || count($vulnerabilities) || $childrenVulns || $computedVulnerabilities): ?>
<div class="vulnerability-element panel panel-yellow">
    <div class="panel-heading">
        Vulnerabilities
    </div>
    <div class="panel-body">
        <?php if (!$isRoot): ?>
            <?php if ($conditionList): ?>
                <h4>Conditions:</h4>
                <div class="ve-conditions context-subsection">
                    <?php if (isset($conditionList) && is_array($conditionList) && count($conditionList)): ?>
                        <div class="ve-conditions">
                            <table class="table">
                            <?php $className = 'even'; ?>
                            <?php foreach ($conditionList as $conditionName => $condition):
                                $propCount = max((count($condition) - 1), 1);
                                $renderedName = false;
                                $className = $className == 'odd' ? 'even' : 'odd';
                                ?>
                                <?php if (is_array($condition)): ?>
                                    <?php foreach ($condition as $propName => $propValue): ?>
                                    <?php if ($propName == 'name') { continue; } ?>
                                    <?php $renderName = false; ?>
                                    <?php if (!$renderedName): $renderName = true; ?><?php endif; ?>
                                    <tr class="<?php echo $className; ?>">
                                        <?php if ($renderName): $renderedName = true; ?>
                                            <td rowspan="<?php echo $propCount; ?>"><strong><?php echo htmlspecialchars($conditionName); ?></strong></td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($propName); ?></td>
                                        <td><?php echo htmlspecialchars(is_bool($propValue) ? ($propValue ? 'Yes' : 'No') : (is_scalar($propValue) ? $propValue : 'not scalar')); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="<?php echo $className; ?>">
                                        <td><strong><?php echo htmlspecialchars($conditionName); ?></strong></td>
                                        <td colspan="2"><?php echo htmlspecialchars(is_bool($condition) ? ($condition ? 'Yes' : 'No') : $condition); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <hr/>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($vulnerabilities) && count($vulnerabilities)): ?>
        <div class="ve-vulnerabilities">
            <table class="table">
                <?php $className = 'even'; ?>
                <?php foreach ($vulnerabilities as $vuln):
                    $propCount = max((count($vuln) - 1), 1);
                    $renderedName = false;
                    $className = $className == 'odd' ? 'even' : 'odd';
                    ?>
                    <?php foreach ($vuln as $propName => $propValue): ?>
                        <?php if ($propName == 'name') { continue; } ?>
                        <?php $renderName = false; ?>
                        <?php if (!$renderedName): $renderName = true; ?><?php endif; ?>
                        <tr class="<?php echo $className; ?> <?php echo !empty($vuln['enabled']) ? 'enabled' : ''; ?>">
                            <?php if ($renderName): $renderedName = true; ?>
                                <td rowspan="<?php echo $propCount; ?>"><strong><?php echo htmlspecialchars($vuln['name']); ?></strong></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($propName); ?></td>
                            <td><?php echo htmlspecialchars(is_bool($propValue) ? ($propValue ? 'Yes' : 'No') : $propValue); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if (isset($computedVulnerabilities)): ?>
            <a href="#" class="js-show-computed-vulns">Show computed</a>
            <div class="js-computed-vulns computed-vulns">
                <?php if (count($computedVulnerabilities)): ?>
                <div class="ve-vulnerabilities">
                    <table class="table">
                        <?php $className = 'even'; ?>
                        <?php foreach ($computedVulnerabilities as $vuln):
                            $propCount = max((count($vuln) - 1), 1);
                            $renderedName = false;
                            $className = $className == 'odd' ? 'even' : 'odd';
                            ?>
                            <?php foreach ($vuln as $propName => $propValue): ?>
                                <?php if ($propName == 'name') { continue; } ?>
                                <?php $renderName = false; ?>
                                <?php if (!$renderedName): $renderName = true; ?><?php endif; ?>
                                <tr class="<?php echo $className; ?> <?php echo !empty($vuln['enabled']) ? 'enabled' : ''; ?>">
                                    <?php if ($renderName): $renderedName = true; ?>
                                        <td rowspan="<?php echo $propCount; ?>"><strong><?php echo htmlspecialchars($vuln['name']); ?></strong></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($propName); ?></td>
                                    <td><?php echo htmlspecialchars(is_bool($propValue) ? ($propValue ? 'Yes' : 'No') : $propValue); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($childrenVulns): ?>
            <h4>Children:</h4>
            <div class="ve-children context-subsection">
                <?php echo $childrenVulns; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
